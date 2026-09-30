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
        $ids = array_values(array_unique($command->orderIds));
        foreach ($ids as $id) {
            $this->deletion->delete($this->orders->get(Ulid::fromString($id)));
        }
        $this->transaction->commit();

        return \count($ids);
    }
}
