<?php

declare(strict_types=1);

namespace App\Application\Purchasing\GetSupplierOrder;

use App\Application\Purchasing\SupplierOrderView;
use App\Domain\Purchasing\SupplierOrderRepository;
use Symfony\Component\Uid\Ulid;

final readonly class GetSupplierOrderHandler
{
    public function __construct(
        private SupplierOrderRepository $orders,
    ) {
    }

    public function __invoke(string $orderId): SupplierOrderView
    {
        return SupplierOrderView::of($this->orders->get(Ulid::fromString($orderId)));
    }
}
