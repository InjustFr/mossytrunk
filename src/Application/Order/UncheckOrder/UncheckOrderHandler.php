<?php

declare(strict_types=1);

namespace App\Application\Order\UncheckOrder;

use App\Application\Transaction;
use App\Domain\Order\OrderRepository;
use Symfony\Component\Uid\Ulid;

final readonly class UncheckOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $orderId): void
    {
        $this->orders->get(Ulid::fromString($orderId))->uncheck();
        $this->transaction->commit();
    }
}
