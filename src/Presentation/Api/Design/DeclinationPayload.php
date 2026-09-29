<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\AdjustDeclination\AdjustDeclination;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class DeclinationPayload
{
    /**
     * @param list<string> $variants
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Le nom du produit est obligatoire.')]
        #[Assert\Length(max: 255)]
        public string $productName = '',
        #[Assert\PositiveOrZero(message: 'Le prix de vente ne peut pas être négatif.')]
        public int $sellingPrice = 0,
        #[Assert\All([new Assert\Type('string'), new Assert\NotBlank(message: 'Une variante ne peut pas être vide.')])]
        #[Assert\Unique(message: 'Les variantes doivent être uniques.')]
        public array $variants = [],
    ) {
    }

    public function toCommand(string $designId, string $declinationId): AdjustDeclination
    {
        return new AdjustDeclination($designId, $declinationId, $this->productName, $this->sellingPrice, $this->variants);
    }
}
