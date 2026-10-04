<?php

declare(strict_types=1);

namespace App\Application\Order;

use App\Domain\Order\Order;
use App\Domain\Order\OrderRepository;
use App\Domain\Stock\LotRevision;
use App\Domain\Stock\StockItem;

final readonly class RevisedLotCosts
{
    public function __construct(private OrderRepository $orders)
    {
    }

    public function follow(StockItem $item, LotRevision $revision): void
    {
        $productId = $item->product()->id();
        $orders = [];
        foreach ([...$this->orders->selling($productId), ...$this->orders->using($productId)] as $order) {
            $orders[spl_object_id($order)] = $order;
        }
        array_map(static fn (Order $order): int => $order->followLot($productId, $item->variant(), $revision), $orders);
    }
}
