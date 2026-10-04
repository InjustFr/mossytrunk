<?php

declare(strict_types=1);

namespace App\Application\Product\BatchUpdateProducts;

final readonly class BatchUpdateProducts
{
    /**
     * @param list<string> $productIds
     * @param list<string> $addVariants
     * @param list<string> $removeVariants
     */
    public function __construct(
        public array $productIds,
        public ?int $sellingPriceCents = null,
        public bool $changeType = false,
        public ?string $typeId = null,
        public array $addVariants = [],
        public array $removeVariants = [],
        public ?int $lowStockThreshold = null,
        public ?ChannelPriceChange $channelPrice = null,
        public ?string $priceSinceDay = null,
    ) {
    }
}
