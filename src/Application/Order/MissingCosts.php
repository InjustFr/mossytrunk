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

    /**
     * @param list<StockItem> $items
     */
    public function fill(array $items): int
    {
        $byProduct = [];
        foreach ($items as $item) {
            $unitCost = $item->firstPurchaseUnitCost();
            if (null !== $unitCost) {
                $byProduct[(string) $item->product()->id()][] = [$item, $unitCost];
            }
        }

        $filled = 0;
        foreach ($byProduct as $costs) {
            $productId = $costs[0][0]->product()->id();
            foreach ($this->orders->selling($productId) as $order) {
                foreach ($costs as [$item, $unitCost]) {
                    $filled += $order->fillMissingCosts($productId, $item->variant(), $unitCost);
                }
            }
        }

        return $filled;
    }
}
