<?php

declare(strict_types=1);

namespace App\Application\Order\DeleteAllOrders;

use App\Application\Stock\StockKeeper;
use App\Application\Transaction;
use App\Domain\Order\OrderRepository;

final readonly class DeleteAllOrdersHandler
{
    public function __construct(
        private OrderRepository $orders,
        private StockKeeper $stock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(): int
    {
        $orders = $this->orders->list();
        foreach ($orders as $order) {
            $this->stock->putBack($order);
            $this->orders->remove($order);
        }
        $this->transaction->commit();

        return \count($orders);
    }
}
