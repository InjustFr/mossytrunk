<?php

declare(strict_types=1);

namespace App\Presentation\Api\Discount;

use App\Application\Discount\DiscountRuleDefinition;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class DiscountRulePayload
{
    /**
     * @param list<DiscountConditionPayload> $conditions
     */
    public function __construct(
        #[Assert\NotBlank(message: 'name.required')]
        #[Assert\Length(max: 255)]
        public string $name = '',
        #[Assert\Count(min: 1, minMessage: 'discount.conditions.atLeastOne')]
        #[Assert\Valid]
        public array $conditions = [],
        #[Assert\Valid]
        public DiscountActionPayload $action = new DiscountActionPayload(),
        #[Assert\Date(message: 'date.start.invalid')]
        public ?string $startsOn = null,
        #[Assert\Date(message: 'date.end.invalid')]
        public ?string $endsOn = null,
    ) {
    }

    public function toDefinition(): DiscountRuleDefinition
    {
        return new DiscountRuleDefinition(
            $this->name,
            array_map(static fn (DiscountConditionPayload $condition) => $condition->toDefinition(), $this->conditions),
            $this->action->kind,
            $this->action->value,
            $this->startsOn,
            $this->endsOn,
        );
    }
}
