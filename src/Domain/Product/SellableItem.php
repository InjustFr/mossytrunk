<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

final readonly class SellableItem
{
    public function __construct(
        public ?Ulid $productId,
        public ?string $variant,
        public string $productName,
        public Money $sellingPrice,
        public Money $buyingPrice,
        public ?Ulid $typeId,
    ) {
    }

    public static function unknown(string $label, Money $sellingPrice): self
    {
        return new self(null, null, $label, $sellingPrice, Money::zero(), null);
    }

    public function isKnown(): bool
    {
        return null !== $this->productId;
    }

    public function at(Money $sellingPrice): self
    {
        return new self($this->productId, $this->variant, $this->productName, $sellingPrice, $this->buyingPrice, $this->typeId);
    }

    public function label(): string
    {
        return VariantLabel::display($this->productName, $this->variant);
    }
}
