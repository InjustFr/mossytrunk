<?php

declare(strict_types=1);

namespace App\Application\Order\DeleteOrders;

use App\Application\Order\OrderDeletion;
use App\Application\Transaction;
use App\Domain\Order\OrderRepository;
use Symfony\Component\Uid\Ulid;

final readonly class DeleteOrdersHandler
{
    public function __construct(
        private OrderRepository $orders,
        private OrderDeletion $deletion,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(DeleteOrders $command): int
    {
        $orders = $this->orders->getMany(array_map(Ulid::fromString(...), array_values(array_unique($command->orderIds))));
        foreach ($orders as $order) {
            $this->deletion->delete($order);
        }
        $this->transaction->commit();

        return \count($orders);
    }
}
