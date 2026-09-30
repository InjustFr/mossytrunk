<?php

declare(strict_types=1);

namespace App\Application\Product\Variants;

use App\Domain\Design\DesignRepository;
use App\Domain\Design\Gabarit;
use App\Domain\Design\GabaritRepository;
use App\Domain\Discount\DiscountRuleRepository;
use App\Domain\Product\Exception\VariantInUse;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\ProductType;

final readonly class VariantUsage
{
    public function __construct(
        private ProductRepository $products,
        private GabaritRepository $gabarits,
        private DesignRepository $designs,
        private DiscountRuleRepository $discountRules,
    ) {
    }

    public function assertUnused(ProductType $type, string $variant): void
    {
        $used = array_any($this->products->ofType($type), static fn (Product $product): bool => $product->hasVariant($variant))
            || array_any($this->gabarits->all(), static fn (Gabarit $gabarit): bool => $gabarit->type() === $type && $gabarit->usesVariant($variant))
            || array_any($this->designs->all(), static fn ($design): bool => $design->usesVariant($type, $variant))
            || array_any($this->discountRules->all(), static fn ($rule): bool => $rule->usesVariant($type, $variant));

        if ($used) {
            throw new VariantInUse($variant);
        }
    }
}
