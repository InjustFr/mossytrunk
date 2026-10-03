<?php

declare(strict_types=1);

namespace App\Application\Order\CheckOrder;

use App\Application\Transaction;
use App\Domain\Order\OrderRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Ulid;

final readonly class CheckOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private ClockInterface $clock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $orderId): void
    {
        $this->orders->get(Ulid::fromString($orderId))->check($this->clock->now());
        $this->transaction->commit();
    }
}
