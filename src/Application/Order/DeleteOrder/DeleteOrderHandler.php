<?php

declare(strict_types=1);

namespace App\Application\Order\DeleteOrder;

use App\Application\Transaction;
use App\Domain\Order\OrderRepository;
use Symfony\Component\Uid\Ulid;

final readonly class DeleteOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $orderId): void
    {
        $this->orders->remove($this->orders->get(Ulid::fromString($orderId)));
        $this->transaction->commit();
    }
}
