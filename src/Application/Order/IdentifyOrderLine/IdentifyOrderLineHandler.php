<?php

declare(strict_types=1);

namespace App\Application\Order\IdentifyOrderLine;

use App\Application\Stock\StockKeeper;
use App\Application\Transaction;
use App\Domain\Order\OrderedItem;
use App\Domain\Order\OrderRepository;
use App\Domain\Product\ProductRepository;
use Symfony\Component\Uid\Ulid;

final readonly class IdentifyOrderLineHandler
{
    public function __construct(
        private OrderRepository $orders,
        private ProductRepository $products,
        private StockKeeper $stock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(IdentifyOrderLine $command): void
    {
        $order = $this->orders->get(Ulid::fromString($command->orderId));
        $line = $order->line(Ulid::fromString($command->lineId));
        $item = $this->products->get(Ulid::fromString($command->productId))->sellable($command->variant)->at($line->unitPrice());

        $withdrawn = $this->stock->withdraw($order->event(), [new OrderedItem($item, $line->quantity())]);

        $line->identify($item, $withdrawn[0]->cost());
        $this->transaction->commit();
    }
}
