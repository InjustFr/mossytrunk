<?php

declare(strict_types=1);

namespace App\Application\Purchasing\ReviseSupplierOrder;

use App\Application\Purchasing\PurchasedItems;
use App\Application\Purchasing\SupplierOrderDraft;
use App\Application\Transaction;
use App\Domain\Purchasing\SupplierOrderRepository;
use App\Domain\Purchasing\SupplierRepository;
use Symfony\Component\Uid\Ulid;

final readonly class ReviseSupplierOrderHandler
{
    public function __construct(
        private SupplierRepository $suppliers,
        private SupplierOrderRepository $orders,
        private PurchasedItems $items,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $orderId, SupplierOrderDraft $draft): void
    {
        $this->orders->get(Ulid::fromString($orderId))->revise(
            $this->suppliers->get(Ulid::fromString($draft->supplierId)),
            $draft->orderedOn,
            $this->items->of($draft->lines),
        );
        $this->transaction->commit();
    }
}
