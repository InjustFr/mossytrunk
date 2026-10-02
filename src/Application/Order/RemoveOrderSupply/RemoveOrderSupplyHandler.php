<?php

declare(strict_types=1);

namespace App\Application\Order\RemoveOrderSupply;

use App\Application\Stock\StockKeeper;
use App\Application\Transaction;
use App\Domain\Order\OrderRepository;
use Symfony\Component\Uid\Ulid;

final readonly class RemoveOrderSupplyHandler
{
    public function __construct(
        private OrderRepository $orders,
        private StockKeeper $stock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $orderId, string $supplyLineId): void
    {
        $this->stock->giveBack($this->orders->get(Ulid::fromString($orderId))->returnSupply(Ulid::fromString($supplyLineId)));
        $this->transaction->commit();
    }
}
