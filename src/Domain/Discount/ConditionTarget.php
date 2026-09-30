<?php

declare(strict_types=1);

namespace App\Domain\Discount;

use App\Domain\Product\Product;
use App\Domain\Product\ProductType;
use App\Domain\Product\VariantLabel;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'discount_condition_target')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'kind', type: 'string', length: 16)]
#[ORM\DiscriminatorMap(['product' => ProductTarget::class, 'type' => TypeTarget::class])]
abstract class ConditionTarget
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: DiscountCondition::class, inversedBy: 'targets')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private DiscountCondition $condition;

    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $variant = null;

    protected function __construct(DiscountCondition $condition)
    {
        $this->id = new Ulid();
        $this->condition = $condition;
    }

    public static function of(DiscountCondition $condition, TargetSpec $spec): self
    {
        return $spec->target instanceof Product
            ? new ProductTarget($condition, $spec->target, $spec->variant)
            : new TypeTarget($condition, $spec->target, $spec->variant);
    }

    abstract protected function matchesSubject(Ulid $productId, Ulid $typeId): bool;

    abstract protected function specificityOfSubject(): int;

    abstract protected function nameOfSubject(): string;

    abstract public function subject(): Product|ProductType;

    abstract public function concerns(ProductType $type): bool;

    abstract public function kind(): string;

    public function matches(Ulid $productId, Ulid $typeId, ?string $variant): bool
    {
        return $this->matchesSubject($productId, $typeId) && (null === $this->variant || VariantLabel::same($this->variant, $variant));
    }

    public function is(Product|ProductType $subject, ?string $variant): bool
    {
        return $subject === $this->subject() && VariantLabel::same($this->variant, $variant);
    }

    public function specificity(): int
    {
        return $this->specificityOfSubject() + (null === $this->variant ? 0 : 1);
    }

    public function name(): string
    {
        return null === $this->variant ? $this->nameOfSubject() : \sprintf('%s · %s', $this->nameOfSubject(), $this->variant);
    }

    public function renameVariant(string $from, string $to): void
    {
        if (VariantLabel::same($this->variant, $from)) {
            $this->variant = $to;
        }
    }

    public function subjectId(): Ulid
    {
        return $this->subject()->id();
    }

    public function variant(): ?string
    {
        return $this->variant;
    }
}
