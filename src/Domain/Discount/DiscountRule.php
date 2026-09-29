<?php

declare(strict_types=1);

namespace App\Domain\Discount;

use App\Domain\Identity\Workspace;
use App\Domain\Product\Product;
use App\Domain\Product\ProductType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'discount_rule')]
class DiscountRule
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\Column(length: 255)]
    private string $name;

    /** @var Collection<int, DiscountCondition> */
    #[ORM\OneToMany(targetEntity: DiscountCondition::class, mappedBy: 'rule', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $conditions;

    #[ORM\Embedded(class: DiscountAction::class, columnPrefix: 'action_')]
    private DiscountAction $action;

    #[ORM\Embedded(class: ValidityPeriod::class, columnPrefix: 'valid_')]
    private ValidityPeriod $validity;

    /**
     * @param list<ConditionSpec> $conditions
     */
    private function __construct(Ulid $id, Workspace $workspace, string $name, array $conditions, DiscountAction $action, ValidityPeriod $validity)
    {
        $this->id = $id;
        $this->workspace = $workspace;
        $this->conditions = new ArrayCollection();
        $this->redefine($name, $conditions, $action, $validity);
    }

    /**
     * @param list<ConditionSpec> $conditions
     */
    public static function create(Workspace $workspace, string $name, array $conditions, DiscountAction $action, ?ValidityPeriod $validity = null): self
    {
        return new self(new Ulid(), $workspace, $name, $conditions, $action, $validity ?? ValidityPeriod::always());
    }

    /**
     * @param list<ConditionSpec> $conditions
     */
    public function redefine(string $name, array $conditions, DiscountAction $action, ?ValidityPeriod $validity = null): void
    {
        $name = trim($name);
        if ('' === $name) {
            throw InvalidDiscountRule::emptyName();
        }
        if ([] === $conditions) {
            throw InvalidDiscountRule::noCondition();
        }

        $built = [];
        foreach ($conditions as $spec) {
            foreach ($built as $condition) {
                if ($condition->targets($spec->target)) {
                    throw InvalidDiscountRule::duplicateTarget($condition->targetName());
                }
            }
            $built[] = $spec->target instanceof Product
                ? new ProductCondition($this, $spec->quantity, $spec->target)
                : new TypeCondition($this, $spec->quantity, $spec->target);
        }

        $this->name = $name;
        $this->action = $action;
        $this->validity = $validity ?? ValidityPeriod::always();
        $this->conditions->clear();
        foreach ($built as $condition) {
            $this->conditions->add($condition);
        }
    }

    public function replaceProduct(Product $replaced, Product $by): void
    {
        $source = $this->conditionOn($replaced);
        if (!$source instanceof ProductCondition) {
            return;
        }

        $target = $this->conditionOn($by);
        if (null === $target) {
            $source->retarget($by);

            return;
        }
        $target->add($source->quantity());
        $this->conditions->removeElement($source);
    }

    public function withdrawProduct(Product $product): void
    {
        $condition = $this->conditionOn($product);
        if (null === $condition) {
            return;
        }
        if (1 === $this->conditions->count()) {
            throw InvalidDiscountRule::onlyEligibleProduct($this->name, $product->displayName());
        }
        $this->conditions->removeElement($condition);
    }

    public function listsTypes(): bool
    {
        return $this->conditions->exists(static fn (int $key, DiscountCondition $condition): bool => $condition instanceof TypeCondition);
    }

    public function withdrawEveryProduct(): void
    {
        if (!$this->listsTypes()) {
            throw InvalidDiscountRule::noCondition();
        }
        foreach ($this->conditions->toArray() as $condition) {
            if ($condition instanceof ProductCondition) {
                $this->conditions->removeElement($condition);
            }
        }
    }

    public function startOn(\DateTimeImmutable $today): void
    {
        match ($this->statusOn($today)) {
            DiscountStatus::Expired => throw InvalidDiscountRule::expired($this->name),
            DiscountStatus::Upcoming => $this->validity = $this->validity->startingOn($today),
            DiscountStatus::Running => null,
        };
    }

    public function stopBefore(\DateTimeImmutable $today): void
    {
        if (DiscountStatus::Running !== $this->statusOn($today)) {
            throw InvalidDiscountRule::notRunning($this->name);
        }

        $this->validity = $this->validity->endingBefore($today);
    }

    public function statusOn(\DateTimeImmutable $moment): DiscountStatus
    {
        return $this->validity->statusOn($moment);
    }

    public function appliesOn(\DateTimeImmutable $moment): bool
    {
        return $this->validity->covers($moment);
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return list<DiscountCondition>
     */
    public function conditions(): array
    {
        return array_values($this->conditions->toArray());
    }

    /**
     * @return list<DiscountCondition>
     */
    public function conditionsMostSpecificFirst(): array
    {
        $conditions = $this->conditions();
        usort($conditions, static fn (DiscountCondition $a, DiscountCondition $b): int => $b->isSpecific() <=> $a->isSpecific());

        return $conditions;
    }

    public function action(): DiscountAction
    {
        return $this->action;
    }

    public function validity(): ValidityPeriod
    {
        return $this->validity;
    }

    public function workspace(): Workspace
    {
        return $this->workspace;
    }

    private function conditionOn(Product|ProductType $target): ?DiscountCondition
    {
        return $this->conditions->findFirst(static fn (int $key, DiscountCondition $condition): bool => $condition->targets($target));
    }
}
