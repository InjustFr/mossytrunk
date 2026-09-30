<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateProductTypePayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le nom du type est obligatoire.')]
        #[Assert\Length(max: 100)]
        public string $name = '',
        #[Assert\NotBlank(message: 'Choisissez une couleur.')]
        #[Assert\Regex(pattern: '/^#[0-9a-fA-F]{6}$/', message: 'Choisissez une couleur.')]
        public string $color = '',
    ) {
    }
}
