<?php

declare(strict_types=1);

namespace App\Application\Order\MergeOrders;

use App\Application\Transaction;
use App\Domain\Order\OrderRepository;
use Symfony\Component\Uid\Ulid;

final readonly class MergeOrdersHandler
{
    public function __construct(
        private OrderRepository $orders,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $orderId, string $absorbedOrderId): void
    {
        $order = $this->orders->get(Ulid::fromString($orderId));
        $absorbed = $this->orders->get(Ulid::fromString($absorbedOrderId));

        $order->absorb($absorbed);
        $this->orders->remove($absorbed);
        $this->transaction->commit();
    }
}
