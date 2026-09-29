<?php

declare(strict_types=1);

namespace App\Domain\Order;

use App\Domain\Product\SellableItem;
use App\Domain\Shared\Money;

/**
 * A validated (product, variant) tuple and how many units are bought.
 */
final readonly class OrderedItem
{
    public function __construct(
        public SellableItem $item,
        public int $quantity,
        private ?Money $cost = null,
    ) {
    }

    public function costing(Money $cost): self
    {
        return new self($this->item, $this->quantity, $cost);
    }

    public function cost(): Money
    {
        return $this->cost ?? $this->item->buyingPrice->multiply($this->quantity);
    }
}
