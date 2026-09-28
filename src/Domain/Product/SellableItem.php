<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

/**
 * What a customer actually buys: a product, and its variant when the product has variants.
 * Only obtainable through {@see Product::sellable()}, so the (product, variant) tuple is always valid.
 * Carries the prices at the time of sale so orders can snapshot them.
 */
final readonly class SellableItem
{
    /**
     * @internal use Product::sellable()
     */
    public function __construct(
        public Ulid $productId,
        public ?string $variant,
        public string $productName,
        public Money $sellingPrice,
        public Money $buyingPrice,
    ) {
    }

    public function label(): string
    {
        return null === $this->variant ? $this->productName : \sprintf('%s — %s', $this->productName, $this->variant);
    }

    public function isSameAs(self $other): bool
    {
        return $this->productId->equals($other->productId) && $this->variant === $other->variant;
    }
}
