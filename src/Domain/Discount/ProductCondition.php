<?php

declare(strict_types=1);

namespace App\Domain\Discount;

use App\Domain\Product\Product;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
class ProductCondition extends DiscountCondition
{
    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private Product $product;

    public function __construct(DiscountRule $rule, int $quantity, Product $product)
    {
        parent::__construct($rule, $quantity);
        $this->product = $product;
    }

    public function matches(Ulid $productId, ?Ulid $typeId): bool
    {
        return $this->product->id()->equals($productId);
    }

    public function targets(object $target): bool
    {
        return $target === $this->product;
    }

    public function retarget(Product $product): void
    {
        $this->product = $product;
    }

    public function kind(): string
    {
        return 'product';
    }

    public function targetId(): Ulid
    {
        return $this->product->id();
    }

    public function targetName(): string
    {
        return $this->product->displayName();
    }

    public function isSpecific(): bool
    {
        return true;
    }
}
