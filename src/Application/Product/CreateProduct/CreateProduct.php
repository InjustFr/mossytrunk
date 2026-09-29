<?php

declare(strict_types=1);

namespace App\Application\Product\CreateProduct;

use App\Domain\Product\Product;

final readonly class CreateProduct
{
    /**
     * @param list<string> $variants
     */
    public function __construct(
        public string $name,
        public int $sellingPriceCents,
        public int $buyingPriceCents = 0,
        public array $variants = [],
        public ?string $typeId = null,
        public int $lowStockThreshold = Product::DEFAULT_LOW_STOCK_THRESHOLD,
    ) {
    }
}
