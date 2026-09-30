<?php

declare(strict_types=1);

namespace App\Domain\Discount;

use App\Domain\Product\Product;
use App\Domain\Product\ProductType;

final readonly class ConditionSpec
{
    public function __construct(
        public int $quantity,
        public Product|ProductType $target,
        public ?string $variant = null,
    ) {
    }
}
