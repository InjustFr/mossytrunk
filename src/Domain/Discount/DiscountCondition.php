<?php

declare(strict_types=1);

namespace App\Domain\Discount;

use App\Domain\Discount\Exception\ConditionQuantityTooSmall;
use App\Domain\Product\VariantLabel;
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

    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $variant = null;

    protected function __construct(DiscountRule $rule, int $quantity)
    {
        if ($quantity < 1) {
            throw new ConditionQuantityTooSmall();
        }
        $this->id = new Ulid();
        $this->rule = $rule;
        $this->quantity = $quantity;
    }

    abstract protected function matchesTarget(Ulid $productId, Ulid $typeId): bool;

    abstract protected function isOn(object $target): bool;

    abstract protected function specificityOfTarget(): int;

    abstract protected function nameOfTarget(): string;

    abstract public function kind(): string;

    abstract public function targetId(): Ulid;

    public function matches(Ulid $productId, Ulid $typeId, ?string $variant): bool
    {
        return $this->matchesTarget($productId, $typeId) && (null === $this->variant || VariantLabel::same($this->variant, $variant));
    }

    public function targets(object $target, ?string $variant): bool
    {
        return $this->isOn($target) && VariantLabel::same($this->variant, $variant);
    }

    public function specificity(): int
    {
        return $this->specificityOfTarget() + (null === $this->variant ? 0 : 1);
    }

    public function targetName(): string
    {
        return null === $this->variant ? $this->nameOfTarget() : \sprintf('%s · %s', $this->nameOfTarget(), $this->variant);
    }

    public function renameVariant(string $from, string $to): void
    {
        if (VariantLabel::same($this->variant, $from)) {
            $this->variant = $to;
        }
    }

    public function variant(): ?string
    {
        return $this->variant;
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
