<?php

declare(strict_types=1);

namespace App\Presentation\Api\Discount;

use App\Application\Discount\ConditionDefinition;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class DiscountConditionPayload
{
    /**
     * @param list<DiscountTargetPayload> $targets
     */
    public function __construct(
        #[Assert\Positive(message: 'quantity.atLeastOne')]
        public int $quantity = 1,
        #[Assert\Count(min: 1, minMessage: 'discount.condition.targets.atLeastOne')]
        #[Assert\Valid]
        public array $targets = [],
    ) {
    }

    public function toDefinition(): ConditionDefinition
    {
        return new ConditionDefinition($this->quantity, array_map(static fn (DiscountTargetPayload $target) => $target->toDefinition(), $this->targets));
    }
}
