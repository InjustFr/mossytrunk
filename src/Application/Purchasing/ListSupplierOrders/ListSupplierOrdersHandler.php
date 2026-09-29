<?php

declare(strict_types=1);

namespace App\Application\Purchasing\ListSupplierOrders;

use App\Application\Purchasing\SupplierOrderView;
use App\Domain\Purchasing\SupplierOrderRepository;

final readonly class ListSupplierOrdersHandler
{
    public function __construct(
        private SupplierOrderRepository $orders,
    ) {
    }

    /**
     * @return list<SupplierOrderView>
     */
    public function __invoke(): array
    {
        return array_map(SupplierOrderView::of(...), $this->orders->all());
    }
}
