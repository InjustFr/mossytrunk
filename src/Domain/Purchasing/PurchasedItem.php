<?php

declare(strict_types=1);

namespace App\Domain\Purchasing;

use App\Domain\Product\SellableItem;
use App\Domain\Shared\Money;

final readonly class PurchasedItem
{
    public function __construct(
        public SellableItem $item,
        public int $quantity,
        public Money $totalPrice,
    ) {
    }
}
