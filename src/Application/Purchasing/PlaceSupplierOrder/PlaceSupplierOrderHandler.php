<?php

declare(strict_types=1);

namespace App\Application\Purchasing\PlaceSupplierOrder;

use App\Application\Purchasing\PurchasedItems;
use App\Application\Purchasing\SupplierOrderDraft;
use App\Application\Transaction;
use App\Domain\Purchasing\SupplierOrder;
use App\Domain\Purchasing\SupplierOrderRepository;
use App\Domain\Purchasing\SupplierRepository;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

final readonly class PlaceSupplierOrderHandler
{
    public function __construct(
        private SupplierRepository $suppliers,
        private SupplierOrderRepository $orders,
        private PurchasedItems $items,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(SupplierOrderDraft $draft): Ulid
    {
        $order = SupplierOrder::place($this->suppliers->get(Ulid::fromString($draft->supplierId)), $draft->orderedOn, $this->items->of($draft->lines), Money::cents($draft->discountCents), Money::cents($draft->deliveryFeesCents));
        $order->referToSupplierOrder($draft->supplierReference);
        $this->orders->add($order);
        $this->transaction->commit();

        return $order->id();
    }
}
