<?php

declare(strict_types=1);

namespace App\Application\Order\StampOrder;

use App\Application\Transaction;
use App\Domain\Order\OrderRepository;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

final readonly class StampOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $orderId, int $postageCents): void
    {
        $this->orders->get(Ulid::fromString($orderId))->stamp(Money::cents($postageCents));
        $this->transaction->commit();
    }
}
