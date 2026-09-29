<?php

declare(strict_types=1);

namespace App\Application\Purchasing\DeleteSupplierOrder;

use App\Application\Transaction;
use App\Domain\Purchasing\SupplierOrderRepository;
use Symfony\Component\Uid\Ulid;

final readonly class DeleteSupplierOrderHandler
{
    public function __construct(
        private SupplierOrderRepository $orders,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $orderId): void
    {
        $order = $this->orders->get(Ulid::fromString($orderId));
        $order->assertStillOrdered();
        $this->orders->remove($order);
        $this->transaction->commit();
    }
}
