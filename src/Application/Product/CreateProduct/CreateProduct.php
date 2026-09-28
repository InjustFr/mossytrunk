<?php

declare(strict_types=1);

namespace App\Application\Product\CreateProduct;

final readonly class CreateProduct
{
    /**
     * @param list<string> $variants
     */
    public function __construct(
        public string $reference,
        public string $name,
        public int $sellingPriceCents,
        public int $buyingPriceCents = 0,
        public array $variants = [],
        public ?string $typeId = null,
    ) {
    }
}
