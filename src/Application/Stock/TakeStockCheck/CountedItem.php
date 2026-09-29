<?php

declare(strict_types=1);

namespace App\Application\Stock\TakeStockCheck;

final readonly class CountedItem
{
    public function __construct(
        public string $productId,
        public ?string $variant,
        public int $counted,
    ) {
    }
}
