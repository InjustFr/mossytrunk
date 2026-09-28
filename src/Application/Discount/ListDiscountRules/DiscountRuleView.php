<?php

declare(strict_types=1);

namespace App\Application\Discount\ListDiscountRules;

use App\Domain\Discount\DiscountRule;
use App\Domain\Product\Product;

final readonly class DiscountRuleView
{
    /**
     * @param list<array{id: string, name: string}> $products
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $products,
        public int $bundleSize,
        public int $bundlePrice,
        public bool $active,
    ) {
    }

    public static function fromRule(DiscountRule $rule): self
    {
        return new self(
            (string) $rule->id(),
            $rule->name(),
            array_map(static fn (Product $product): array => ['id' => (string) $product->id(), 'name' => $product->displayName()], $rule->eligibleProducts()),
            $rule->bundleSize(),
            $rule->bundlePrice()->amount(),
            $rule->isActive(),
        );
    }
}
