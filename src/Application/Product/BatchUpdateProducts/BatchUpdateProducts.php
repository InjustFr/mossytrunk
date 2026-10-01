<?php

declare(strict_types=1);

namespace App\Application\Product\BatchUpdateProducts;

/**
 * Same changes applied to several products. Null / empty means "leave as is".
 */
final readonly class BatchUpdateProducts
{
    /**
     * @param list<string> $productIds
     * @param list<string> $addVariants    added when missing (existing ones are kept)
     * @param list<string> $removeVariants removed when present
     */
    public function __construct(
        public array $productIds,
        public ?int $sellingPriceCents = null,
        public bool $changeType = false,
        public ?string $typeId = null,
        public array $addVariants = [],
        public array $removeVariants = [],
        public ?int $lowStockThreshold = null,
    ) {
    }
}
