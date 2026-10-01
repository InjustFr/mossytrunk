<?php

declare(strict_types=1);

namespace App\Application\Product\UpdateProduct;

use App\Domain\Product\Product;

final readonly class UpdateProduct
{
    /**
     * @param list<string>        $variants
     * @param array<string, ?int> $channelPrices
     */
    public function __construct(
        public string $productId,
        public string $name,
        public int $sellingPriceCents,
        public array $variants,
        public ?string $typeId = null,
        public int $lowStockThreshold = Product::DEFAULT_LOW_STOCK_THRESHOLD,
        public ?string $reference = null,
        public array $channelPrices = [],
    ) {
    }
}
