<?php

declare(strict_types=1);

namespace App\Application\Order\RefundOrder;

use App\Application\Stock\StockKeeper;
use App\Application\Transaction;
use App\Domain\Order\OrderRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Ulid;

final readonly class RefundOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private StockKeeper $stock,
        private ClockInterface $clock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $orderId): void
    {
        $order = $this->orders->get(Ulid::fromString($orderId));
        $refundedAt = $this->clock->now();

        $order->refund($refundedAt);
        $this->stock->takeBack($order, $refundedAt);
        $this->transaction->commit();
    }
}
