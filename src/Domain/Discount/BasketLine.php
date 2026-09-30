<?php

declare(strict_types=1);

namespace App\Domain\Discount;

use App\Domain\Product\SellableItem;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

/**
 * Input of the discount calculation: `quantity` units of a product variant (of type `typeId`) at `unitPrice`.
 */
final readonly class BasketLine
{
    public function __construct(
        public Ulid $productId,
        public Money $unitPrice,
        public int $quantity,
        public Ulid $typeId,
        public ?string $variant = null,
    ) {
    }

    public static function of(SellableItem $item, int $quantity): ?self
    {
        return null === $item->productId || null === $item->typeId ? null : new self($item->productId, $item->sellingPrice, $quantity, $item->typeId, $item->variant);
    }
}
