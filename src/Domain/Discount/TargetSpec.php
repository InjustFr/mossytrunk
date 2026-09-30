<?php

declare(strict_types=1);

namespace App\Domain\Discount;

use App\Domain\Product\Product;
use App\Domain\Product\ProductType;

final readonly class TargetSpec
{
    public function __construct(
        public Product|ProductType $target,
        public ?string $variant = null,
    ) {
    }
}
