<?php

declare(strict_types=1);

namespace App\Application\Integration\ImportSales;

use App\Application\Integration\ExternalSale;
use App\Application\Order\OrderCharges;
use App\Domain\Order\Order;
use App\Domain\Order\OrderRepository;

final readonly class SaleFees
{
    public function __construct(
        private OrderRepository $orders,
        private OrderCharges $charges,
    ) {
    }

    /**
     * @param array<array-key, ExternalSale> $sales by external id
     */
    public function settleImported(string $service, array $sales): int
    {
        $updated = 0;
        foreach ($this->orders->awaitingSaleFees($service) as $order) {
            if ($this->settle($order, $service, $sales)) {
                $this->charges->charge($order);
                ++$updated;
            }
        }

        return $updated;
    }

    /**
     * @param array<array-key, ExternalSale> $sales by external id
     */
    private function settle(Order $order, string $service, array $sales): bool
    {
        $settled = false;
        foreach ($order->importedSales() as $imported) {
            $fee = ($sales[$imported->externalId()] ?? null)?->fee;
            if (null !== $fee && null === $imported->fee() && $imported->isFrom($service, $imported->externalId())) {
                $order->settleSaleFee($service, $imported->externalId(), $fee);
                $settled = true;
            }
        }

        return $settled;
    }
}
