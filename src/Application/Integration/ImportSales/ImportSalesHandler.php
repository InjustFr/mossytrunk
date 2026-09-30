<?php

declare(strict_types=1);

namespace App\Application\Integration\ImportSales;

use App\Application\Integration\ConnectionSession;
use App\Application\Integration\Connectors;
use App\Application\Integration\Exception\ServiceNotAdded;
use App\Application\Integration\ExternalSale;
use App\Application\Order\OrderPricing;
use App\Application\Product\CreateProductType\MiscellaneousType;
use App\Application\Product\CreateProductType\ProductTypeCreator;
use App\Application\Stock\StockKeeper;
use App\Application\Transaction;
use App\Application\Translator;
use App\Domain\Event\EventRepository;
use App\Domain\Integration\ExternalItemRepository;
use App\Domain\Integration\SalesContext;
use App\Domain\Integration\ServiceConnectionRepository;
use App\Domain\Order\Order;
use App\Domain\Order\OrderedItem;
use App\Domain\Order\OrderRepository;
use App\Domain\Product\ProductReferenceGenerator;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\ProductTypeRepository;
use App\Domain\Shared\DateRange;
use Psr\Clock\ClockInterface;

final readonly class ImportSalesHandler
{
    public function __construct(
        private Connectors $connectors,
        private ServiceConnectionRepository $connections,
        private ConnectionSession $session,
        private ExternalItemRepository $items,
        private ProductRepository $products,
        private ProductReferenceGenerator $references,
        private ProductTypeRepository $types,
        private ProductTypeCreator $typeCreator,
        private MiscellaneousType $miscellaneous,
        private EventRepository $events,
        private OrderRepository $orders,
        private OrderPricing $pricing,
        private StockKeeper $stock,
        private ClockInterface $clock,
        private Transaction $transaction,
        private Translator $translator,
    ) {
    }

    public function __invoke(string $service): ImportReport
    {
        $connector = $this->connectors->get($service);
        $description = $connector->describe();
        $connection = $this->connections->find($service) ?? throw new ServiceNotAdded($description->label);

        $sales = [];
        foreach ($connector->sales($this->session->credentials($connection)) as $sale) {
            $sales[$sale->id] ??= $sale;
        }
        uasort($sales, static fn (ExternalSale $a, ExternalSale $b): int => $a->placedAt <=> $b->placedAt);
        $alreadyImported = array_flip($this->orders->importedExternalIds($service, array_map(strval(...), array_keys($sales))));

        $catalogue = new ImportedCatalogue($this->products, $this->references, $this->types, $this->typeCreator, $this->miscellaneous, $connection->workspace(), $this->translator->trans('import.free_amount'));
        $resolver = new ExternalItemResolver($catalogue, $this->items, $connection, $description->linePrices, $this->clock->now());
        $atEvent = SalesContext::AtEvent === $connection->salesContext();

        $imported = $withoutEvent = $waiting = $empty = 0;
        $datesWithoutEvent = [];
        foreach ($sales as $id => $sale) {
            if (isset($alreadyImported[(string) $id])) {
                continue;
            }
            if ([] === $sale->lines) {
                ++$empty;
                continue;
            }

            $items = [];
            $resolved = true;
            foreach ($sale->lines as $line) {
                $item = $resolver->resolve($line);
                if (null === $item) {
                    $resolved = false;
                    continue;
                }
                $items[] = new OrderedItem($item, $line->quantity);
            }

            $event = $atEvent ? $this->events->findCovering($sale->placedAt) : null;
            if ($atEvent && null === $event) {
                ++$withoutEvent;
                $datesWithoutEvent[$sale->placedAt->setTimezone(new \DateTimeZone(DateRange::TIMEZONE))->format('Y-m-d')] = true;
                continue;
            }
            if (!$resolved) {
                ++$waiting;
                continue;
            }

            $items = $this->stock->withdraw($event, $items);
            $this->orders->add(Order::imported(
                $connection->workspace(),
                $service,
                $sale->id,
                $sale->reference,
                $event,
                $sale->placedAt,
                $items,
                $sale->charged,
                $sale->shipping,
                $sale->paymentMethod,
                $atEvent ? $this->pricing->discounts($items, $sale->placedAt) : [],
                $this->translator->trans('import.discount', ['service' => $description->label]),
            ));
            ++$imported;
        }

        $this->transaction->commit();

        $dates = array_keys($datesWithoutEvent);
        sort($dates);

        return new ImportReport(
            $service,
            $description->label,
            $imported,
            \count($alreadyImported),
            $catalogue->createdCount(),
            $catalogue->typesCreatedCount(),
            $withoutEvent,
            array_map(strval(...), $dates),
            $waiting,
            $resolver->itemsToLink(),
            $empty,
        );
    }
}
