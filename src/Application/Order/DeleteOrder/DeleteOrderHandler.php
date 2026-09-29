<?php

declare(strict_types=1);

namespace App\Application\Order\DeleteOrder;

use App\Application\Stock\StockKeeper;
use App\Application\Transaction;
use App\Domain\Order\OrderRepository;
use Symfony\Component\Uid\Ulid;

final readonly class DeleteOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private StockKeeper $stock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $orderId): void
    {
        $order = $this->orders->get(Ulid::fromString($orderId));
        $this->stock->putBack($order);
        $this->orders->remove($order);
        $this->transaction->commit();
    }
}
