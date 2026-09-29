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
        #[Assert\NotBlank(message: 'Le nom du gabarit est obligatoire.')]
        #[Assert\Length(max: 255)]
        public string $name = '',
        #[Assert\Ulid(message: 'Type invalide.')]
        public ?string $typeId = null,
        #[Assert\PositiveOrZero(message: 'Le prix de vente ne peut pas être négatif.')]
        public int $sellingPrice = 0,
        #[Assert\PositiveOrZero(message: 'Le prix d\'achat ne peut pas être négatif.')]
        public int $buyingPrice = 0,
        #[Assert\All([new Assert\Type('string'), new Assert\NotBlank(message: 'Une variante ne peut pas être vide.')])]
        public array $variants = [],
        #[Assert\All([new Assert\Type('string'), new Assert\NotBlank(message: 'Une adaptation ne peut pas être vide.')])]
        #[Assert\Unique(message: 'Les adaptations doivent être uniques.')]
        public array $adaptations = [],
    ) {
    }

    public function toCommand(?string $gabaritId = null): SaveGabarit
    {
        return new SaveGabarit($gabaritId, $this->name, $this->typeId, $this->sellingPrice, $this->buyingPrice, $this->variants, $this->adaptations);
    }
}
