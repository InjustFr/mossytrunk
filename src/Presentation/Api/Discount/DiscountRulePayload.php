<?php

declare(strict_types=1);

namespace App\Presentation\Api\Discount;

use App\Application\Discount\DiscountRuleDefinition;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class DiscountRulePayload
{
    /**
     * @param list<string> $productIds
     * @param list<string> $typeIds
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
        #[Assert\Length(max: 255)]
        public string $name = '',
        #[Assert\All([new Assert\Ulid()])]
        public array $productIds = [],
        #[Assert\GreaterThanOrEqual(2, message: 'Un lot contient au moins 2 articles.')]
        public int $bundleSize = 0,
        #[Assert\Positive(message: 'Le prix du lot doit être supérieur à zéro.')]
        public int $bundlePrice = 0,
        #[Assert\All([new Assert\Ulid()])]
        public array $typeIds = [],
    ) {
    }

    public function toDefinition(): DiscountRuleDefinition
    {
        return new DiscountRuleDefinition($this->name, $this->productIds, $this->bundleSize, $this->bundlePrice, $this->typeIds);
    }
}
