<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

/**
 * Units sold of one product (or one variant of it) across a set of orders, before discounts.
 */
final readonly class ProductSales
{
    public function __construct(
        public string $label,
        public Ulid $productId,
        public string $productName,
        public ?string $variant,
        public int $quantity,
        public Money $sales,
        public Money $cost,
        public bool $unknownCost,
    ) {
    }

    public function add(int $quantity, Money $sales, Money $cost, bool $unknownCost): self
    {
        return new self($this->label, $this->productId, $this->productName, $this->variant, $this->quantity + $quantity, $this->sales->add($sales), $this->cost->add($cost), $this->unknownCost || $unknownCost);
    }
}
