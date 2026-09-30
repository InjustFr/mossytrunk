<?php

declare(strict_types=1);

namespace App\Application\Discount;

use App\Domain\Discount\ConditionSpec;
use App\Domain\Discount\DiscountAction;
use App\Domain\Discount\DiscountActionKind;
use App\Domain\Discount\TargetSpec;
use App\Domain\Discount\ValidityPeriod;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\ProductTypeRepository;
use Symfony\Component\Uid\Ulid;

final readonly class DiscountRuleParts
{
    public function __construct(
        private ProductRepository $products,
        private ProductTypeRepository $types,
    ) {
    }

    /**
     * @return list<ConditionSpec>
     */
    public function conditions(DiscountRuleDefinition $definition): array
    {
        return array_map(fn (ConditionDefinition $condition): ConditionSpec => new ConditionSpec(
            $condition->quantity,
            array_map($this->target(...), $condition->targets),
        ), $definition->conditions);
    }

    public function action(DiscountRuleDefinition $definition): DiscountAction
    {
        return DiscountAction::of(DiscountActionKind::from($definition->actionKind), $definition->actionValue);
    }

    public function validity(DiscountRuleDefinition $definition): ValidityPeriod
    {
        return ValidityPeriod::between(self::date($definition->startsOn), self::date($definition->endsOn));
    }

    private function target(TargetDefinition $target): TargetSpec
    {
        return new TargetSpec(
            TargetDefinition::TYPE === $target->kind
                ? $this->types->get(Ulid::fromString($target->targetId))
                : $this->products->get(Ulid::fromString($target->targetId)),
            null === $target->variant || '' === trim($target->variant) ? null : $target->variant,
        );
    }

    private static function date(?string $date): ?\DateTimeImmutable
    {
        return null === $date || '' === $date ? null : new \DateTimeImmutable($date);
    }
}
