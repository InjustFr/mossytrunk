<?php

declare(strict_types=1);

namespace App\Application\Discount;

/**
 * Shared input of the create/update discount rule use cases.
 */
final readonly class DiscountRuleDefinition
{
    /**
     * @param list<string> $productIds
     */
    public function __construct(
        public string $name,
        public array $productIds,
        public int $bundleSize,
        public int $bundlePriceCents,
    ) {
    }
}
