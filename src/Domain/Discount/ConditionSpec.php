<?php

declare(strict_types=1);

namespace App\Domain\Discount;

use App\Domain\Product\Product;
use App\Domain\Product\ProductType;

final readonly class ConditionSpec
{
    /**
     * @param list<TargetSpec> $targets
     */
    public function __construct(
        public int $quantity,
        public array $targets,
    ) {
    }

    public static function on(int $quantity, Product|ProductType $target, ?string $variant = null): self
    {
        return new self($quantity, [new TargetSpec($target, $variant)]);
    }
}
