<?php

declare(strict_types=1);

namespace App\Application\Product\MoveVariant;

final readonly class MoveVariant
{
    public function __construct(
        public string $productId,
        public ?string $variant,
        public ?string $targetProductId,
        public ?string $newProductName,
        public ?string $targetVariant,
    ) {
    }
}
