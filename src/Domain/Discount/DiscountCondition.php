<?php

declare(strict_types=1);

namespace App\Domain\Discount;

use App\Domain\Discount\Exception\ConditionQuantityTooSmall;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'discount_condition')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'kind', type: 'string', length: 16)]
#[ORM\DiscriminatorMap(['product' => ProductCondition::class, 'type' => TypeCondition::class])]
abstract class DiscountCondition
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: DiscountRule::class, inversedBy: 'conditions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private DiscountRule $rule;

    #[ORM\Column]
    private int $quantity;

    protected function __construct(DiscountRule $rule, int $quantity)
    {
        if ($quantity < 1) {
            throw new ConditionQuantityTooSmall();
        }
        $this->id = new Ulid();
        $this->rule = $rule;
        $this->quantity = $quantity;
    }

    abstract public function matches(Ulid $productId, ?Ulid $typeId): bool;

    abstract public function targets(object $target): bool;

    abstract public function kind(): string;

    abstract public function targetId(): Ulid;

    abstract public function targetName(): string;

    public function isSpecific(): bool
    {
        return false;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function add(int $quantity): void
    {
        $this->quantity += $quantity;
    }
}
