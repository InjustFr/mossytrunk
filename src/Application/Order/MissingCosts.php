<?php

declare(strict_types=1);

namespace App\Application\Order;

use App\Domain\Order\OrderRepository;
use App\Domain\Stock\StockItem;

final readonly class MissingCosts
{
    public function __construct(private OrderRepository $orders)
    {
    }

    public function fill(StockItem $item): int
    {
        $unitCost = $item->firstPurchaseUnitCost();
        if (null === $unitCost) {
            return 0;
        }

        $filled = 0;
        foreach ($this->orders->selling($item->product()->id()) as $order) {
            $filled += $order->fillMissingCosts($item->product()->id(), $item->variant(), $unitCost);
        }

        return $filled;
    }
}
