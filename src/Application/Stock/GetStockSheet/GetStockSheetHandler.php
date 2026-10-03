<?php

declare(strict_types=1);

namespace App\Application\Stock\GetStockSheet;

use App\Application\Reporting\ProductSalesLedger;
use App\Domain\Event\EventRepository;
use App\Domain\Product\ProductRepository;
use App\Domain\Stock\StockRepository;
use Symfony\Component\Uid\Ulid;

final readonly class GetStockSheetHandler
{
    public function __construct(
        private EventRepository $events,
        private ProductSalesLedger $sales,
        private ProductRepository $products,
        private StockRepository $stock,
    ) {
    }

    /**
     * @return list<StockSheetLine>
     */
    public function __invoke(string $eventId): array
    {
        $event = $this->events->get(Ulid::fromString($eventId));

        $sold = [];
        foreach ($this->sales->ofEvent($event->id()) as $product) {
            $sold[self::key((string) $product->productId, $product->variant)] = $product->quantity;
        }

        $onHand = [];
        foreach ($this->stock->all() as $item) {
            $onHand[self::key((string) $item->product()->id(), $item->variant())] = $item->onHand();
        }

        $lines = [];
        foreach ($this->products->all() as $product) {
            foreach ($product->hasVariants() ? $product->variants() : [null] as $variant) {
                $key = self::key((string) $product->id(), $variant);
                $lines[] = new StockSheetLine(
                    (string) $product->id(),
                    $variant,
                    $product->sellable($variant)->label(),
                    $product->type()->name(),
                    $onHand[$key] ?? 0,
                    $sold[$key] ?? 0,
                    $product->isSupply(),
                );
            }
        }

        return $lines;
    }

    private static function key(string $productId, ?string $variant): string
    {
        return $productId.'|'.$variant;
    }
}
