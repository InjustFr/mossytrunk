<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\SaveGabarit\SaveGabarit;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class GabaritPayload
{
    /**
     * @param list<string> $variants
     * @param list<string> $adaptations
     */
    public function __construct(
        #[Assert\NotBlank(message: 'template.name.required')]
        #[Assert\Length(max: 255)]
        public string $name = '',
        #[Assert\NotBlank(message: 'productType.required')]
        #[Assert\Ulid(message: 'productType.invalid')]
        public ?string $typeId = null,
        #[Assert\PositiveOrZero(message: 'sellingPrice.negative')]
        public int $sellingPrice = 0,
        #[Assert\All([new Assert\Type('string'), new Assert\NotBlank(message: 'variant.blank')])]
        public array $variants = [],
        #[Assert\All([new Assert\Type('string'), new Assert\NotBlank(message: 'adaptation.blank')])]
        #[Assert\Unique(message: 'adaptation.duplicate')]
        public array $adaptations = [],
    ) {
    }

    public function toCommand(?string $gabaritId = null): SaveGabarit
    {
        return new SaveGabarit($gabaritId, $this->name, $this->typeId, $this->sellingPrice, $this->variants, $this->adaptations);
    }
}
