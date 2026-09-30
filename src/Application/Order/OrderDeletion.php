<?php

declare(strict_types=1);

namespace App\Application\Order;

use App\Application\Stock\StockKeeper;
use App\Domain\Order\Order;
use App\Domain\Order\OrderRepository;

final readonly class OrderDeletion
{
    public function __construct(
        private OrderRepository $orders,
        private StockKeeper $stock,
    ) {
    }

    public function delete(Order $order): void
    {
        $this->stock->putBack($order);
        $this->orders->remove($order);
    }
}
