<?php

declare(strict_types=1);

namespace App\Domain\Order;

use App\Domain\Product\SellableItem;

/**
 * A validated (product, variant) tuple and how many units are bought.
 */
final readonly class OrderedItem
{
    public function __construct(
        public SellableItem $item,
        public int $quantity,
    ) {
    }
}
