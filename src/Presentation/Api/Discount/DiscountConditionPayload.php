<?php

declare(strict_types=1);

namespace App\Presentation\Api\Discount;

use App\Application\Discount\ConditionDefinition;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class DiscountConditionPayload
{
    public function __construct(
        #[Assert\Choice(choices: [ConditionDefinition::PRODUCT, ConditionDefinition::TYPE], message: 'Type de condition invalide.')]
        public string $kind = ConditionDefinition::PRODUCT,
        #[Assert\NotBlank(message: 'Choisissez un produit ou un type.')]
        #[Assert\Ulid(message: 'Produit ou type invalide.')]
        public string $id = '',
        #[Assert\Positive(message: 'La quantité doit être d\'au moins 1.')]
        public int $quantity = 1,
    ) {
    }

    public function toDefinition(): ConditionDefinition
    {
        return new ConditionDefinition($this->kind, $this->id, $this->quantity);
    }
}
