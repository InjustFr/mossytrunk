<?php

declare(strict_types=1);

namespace App\Application\Order\RecomputeOrderCharges;

use App\Application\Order\OrderCharges;
use App\Application\Transaction;
use App\Domain\Order\OrderRepository;
use Symfony\Component\Uid\Ulid;

final readonly class RecomputeOrderChargesHandler
{
    public function __construct(
        private OrderRepository $orders,
        private OrderCharges $charges,
        private Transaction $transaction,
    ) {
    }

    /**
     * @param list<string> $orderIds
     */
    public function __invoke(array $orderIds): int
    {
        $orders = $this->orders->getMany(array_map(Ulid::fromString(...), array_values(array_unique($orderIds))));
        foreach ($orders as $order) {
            $this->charges->charge($order);
        }
        $this->transaction->commit();

        return \count($orders);
    }
}
