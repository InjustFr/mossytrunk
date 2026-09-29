<?php

declare(strict_types=1);

namespace App\Application\SumUp\ImportFromSumUp;

use App\Application\Order\OrderPricing;
use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Application\SumUp\SumUpCredentials;
use App\Application\SumUp\SumUpGateway;
use App\Application\SumUp\SumUpTransaction;
use App\Application\SumUp\SumUpUnavailable;
use App\Application\Transaction;
use App\Application\Workspace\WorkspaceSecrets;
use App\Application\WorkspaceContext;
use App\Domain\Event\EventRepository;
use App\Domain\Identity\SecretName;
use App\Domain\Order\Order;
use App\Domain\Order\OrderedItem;
use App\Domain\Order\OrderRepository;
use App\Domain\Product\ProductReferenceGenerator;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\ProductTypeRepository;
use App\Domain\Shared\DateRange;

/**
 * Imports SumUp products and orders. Idempotent: an already imported transaction is skipped.
 * Orders whose date is not covered by any event are NOT imported; their dates are reported once.
 * See docs/business/sumup-import.md.
 */
final readonly class ImportFromSumUpHandler
{
    public function __construct(
        private SumUpGateway $sumUp,
        private ProductRepository $products,
        private ProductReferenceGenerator $references,
        private ProductTypeRepository $types,
        private CreateProductTypeHandler $createType,
        private EventRepository $events,
        private OrderRepository $orders,
        private Transaction $transaction,
        private WorkspaceContext $workspace,
        private WorkspaceSecrets $secrets,
        private OrderPricing $pricing,
    ) {
    }

    public function __invoke(): SumUpImportReport
    {
        /** @var array<string, SumUpTransaction> $transactions */
        $transactions = [];
        foreach ($this->sumUp->successfulPayments($this->credentials()) as $transaction) {
            $transactions[$transaction->code] ??= $transaction;
        }

        $alreadyImported = array_flip($this->orders->importedSumUpTransactionCodes(array_keys($transactions)));
        $resolver = new SumUpProductResolver($this->products, $this->references, $this->types, $this->createType, $this->workspace);
        $imported = $withoutEvent = $withUnresolved = 0;
        $datesWithoutEvent = $unresolved = [];

        foreach ($transactions as $code => $transaction) {
            if (isset($alreadyImported[$code])) {
                continue;
            }

            // Products are imported even when the order itself cannot be.
            $items = [];
            $resolved = [] !== $transaction->lines;
            if (!$resolved) {
                $unresolved['(paiement sans produit)'] = true;
            }
            foreach ($transaction->lines as $line) {
                $item = $resolver->resolve($line);
                if (null === $item) {
                    $resolved = false;
                    $unresolved[$line->name] = true;
                    continue;
                }
                $items[] = new OrderedItem($item, $line->quantity);
            }

            $event = $this->events->findCovering($transaction->createdAt);
            if (null === $event) {
                ++$withoutEvent;
                $datesWithoutEvent[$transaction->createdAt->setTimezone(new \DateTimeZone(DateRange::TIMEZONE))->format('Y-m-d')] = true;
                continue;
            }
            if (!$resolved) {
                ++$withUnresolved;
                continue;
            }

            $this->orders->add(Order::importFromSumUp(
                $code,
                $event,
                $transaction->createdAt,
                $items,
                $transaction->amountPaid,
                $transaction->paymentMethod,
                $this->pricing->discounts($items, $transaction->createdAt),
            ));
            ++$imported;
        }

        $this->transaction->commit();

        $dates = array_keys($datesWithoutEvent);
        sort($dates);

        return new SumUpImportReport(
            $resolver->createdCount(),
            $resolver->typesCreatedCount(),
            $imported,
            \count($alreadyImported),
            $withoutEvent,
            $dates,
            $withUnresolved,
            array_keys($unresolved),
        );
    }

    private function credentials(): SumUpCredentials
    {
        $workspace = $this->workspace->current();
        $apiKey = $this->secrets->reveal($workspace, SecretName::SumUpApiKey);
        $merchantCode = $workspace->sumUpMerchantCode();

        if (null === $apiKey || null === $merchantCode) {
            throw SumUpUnavailable::notConfigured();
        }

        return new SumUpCredentials($apiKey, $merchantCode);
    }
}
