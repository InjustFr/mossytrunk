<?php

declare(strict_types=1);

namespace App\Domain\Discount;

use App\Domain\Product\ProductType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
class TypeCondition extends DiscountCondition
{
    #[ORM\ManyToOne(targetEntity: ProductType::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ProductType $type;

    public function __construct(DiscountRule $rule, int $quantity, ProductType $type)
    {
        parent::__construct($rule, $quantity);
        $this->type = $type;
    }

    public function matches(Ulid $productId, ?Ulid $typeId): bool
    {
        return null !== $typeId && $this->type->id()->equals($typeId);
    }

    public function targets(object $target): bool
    {
        return $target === $this->type;
    }

    public function kind(): string
    {
        return 'type';
    }

    public function targetId(): Ulid
    {
        return $this->type->id();
    }

    public function targetName(): string
    {
        return $this->type->name();
    }
}
