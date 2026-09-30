<?php

declare(strict_types=1);

namespace App\Application\Order\DeleteAllOrders;

use App\Application\Order\OrderDeletion;
use App\Application\Transaction;
use App\Domain\Order\OrderRepository;

final readonly class DeleteAllOrdersHandler
{
    public function __construct(
        private OrderRepository $orders,
        private OrderDeletion $deletion,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(): int
    {
        $orders = $this->orders->list();
        foreach ($orders as $order) {
            $this->deletion->delete($order);
        }
        $this->transaction->commit();

        return \count($orders);
    }
}
