<?php

declare(strict_types=1);

namespace App\Application\Discount\ListDiscountRules;

use App\Domain\Discount\DiscountCondition;
use App\Domain\Discount\DiscountRule;

final readonly class DiscountRuleView
{
    /**
     * @param list<array{kind: string, id: string, name: string, quantity: int}> $conditions
     * @param array{kind: string, value: int}                                   $action
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $status,
        public array $conditions,
        public array $action,
        public ?string $startsOn,
        public ?string $endsOn,
    ) {
    }

    public static function fromRule(DiscountRule $rule, \DateTimeImmutable $today): self
    {
        return new self(
            (string) $rule->id(),
            $rule->name(),
            $rule->statusOn($today)->value,
            array_map(static fn (DiscountCondition $condition): array => [
                'kind' => $condition->kind(),
                'id' => (string) $condition->targetId(),
                'name' => $condition->targetName(),
                'quantity' => $condition->quantity(),
            ], $rule->conditions()),
            ['kind' => $rule->action()->kind()->value, 'value' => $rule->action()->value()],
            $rule->validity()->start()?->format('Y-m-d'),
            $rule->validity()->end()?->format('Y-m-d'),
        );
    }
}
