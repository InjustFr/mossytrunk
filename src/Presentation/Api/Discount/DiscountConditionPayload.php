<?php

declare(strict_types=1);

namespace App\Presentation\Api\Discount;

use App\Application\Discount\ConditionDefinition;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class DiscountConditionPayload
{
    public function __construct(
        #[Assert\Choice(choices: [ConditionDefinition::PRODUCT, ConditionDefinition::TYPE], message: 'discount.condition.kind.invalid')]
        public string $kind = ConditionDefinition::PRODUCT,
        #[Assert\NotBlank(message: 'discount.condition.target.required')]
        #[Assert\Ulid(message: 'discount.condition.target.invalid')]
        public string $id = '',
        #[Assert\Positive(message: 'quantity.atLeastOne')]
        public int $quantity = 1,
        #[Assert\Length(max: 100)]
        public ?string $variant = null,
    ) {
    }

    public function toDefinition(): ConditionDefinition
    {
        return new ConditionDefinition($this->kind, $this->id, $this->quantity, $this->variant);
    }
}
