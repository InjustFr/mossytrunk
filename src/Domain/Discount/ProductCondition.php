<?php

declare(strict_types=1);

namespace App\Domain\Discount;

use App\Domain\Product\Exception\UnknownVariant;
use App\Domain\Product\Product;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
class ProductCondition extends DiscountCondition
{
    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private Product $product;

    public function __construct(DiscountRule $rule, int $quantity, Product $product, ?string $variant = null)
    {
        parent::__construct($rule, $quantity);
        $this->retarget($product, $variant);
    }

    public function retarget(Product $product, ?string $variant): void
    {
        $this->product = $product;
        $this->variant = null === $variant ? null : ($product->variantNamed($variant) ?? throw new UnknownVariant($product->displayName(), $variant));
    }

    public function product(): Product
    {
        return $this->product;
    }

    public function kind(): string
    {
        return 'product';
    }

    public function targetId(): Ulid
    {
        return $this->product->id();
    }

    protected function matchesTarget(Ulid $productId, Ulid $typeId): bool
    {
        return $this->product->id()->equals($productId);
    }

    protected function isOn(object $target): bool
    {
        return $target === $this->product;
    }

    protected function specificityOfTarget(): int
    {
        return 2;
    }

    protected function nameOfTarget(): string
    {
        return $this->product->displayName();
    }
}
