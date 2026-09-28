<?php

declare(strict_types=1);

namespace App\Application\Product\UpdateProduct;

final readonly class UpdateProduct
{
    /**
     * @param list<string> $variants
     */
    public function __construct(
        public string $productId,
        public string $reference,
        public string $name,
        public int $sellingPriceCents,
        public int $buyingPriceCents,
        public array $variants,
        public ?string $typeId = null,
    ) {
    }
}
