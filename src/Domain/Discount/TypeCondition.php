<?php

declare(strict_types=1);

namespace App\Domain\Discount;

use App\Domain\Product\Exception\UnknownTypeVariant;
use App\Domain\Product\ProductType;
use App\Domain\Product\VariantLabel;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
class TypeCondition extends DiscountCondition
{
    #[ORM\ManyToOne(targetEntity: ProductType::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ProductType $type;

    public function __construct(DiscountRule $rule, int $quantity, ProductType $type, ?string $variant = null)
    {
        parent::__construct($rule, $quantity);
        $this->type = $type;
        $this->variant = null === $variant ? null : (VariantLabel::find($type->variants(), $variant) ?? throw new UnknownTypeVariant($type->name(), $variant));
    }

    public function type(): ProductType
    {
        return $this->type;
    }

    public function kind(): string
    {
        return 'type';
    }

    public function targetId(): Ulid
    {
        return $this->type->id();
    }

    protected function matchesTarget(Ulid $productId, Ulid $typeId): bool
    {
        return $this->type->id()->equals($typeId);
    }

    protected function isOn(object $target): bool
    {
        return $target === $this->type;
    }

    protected function specificityOfTarget(): int
    {
        return 0;
    }

    protected function nameOfTarget(): string
    {
        return $this->type->name();
    }
}
