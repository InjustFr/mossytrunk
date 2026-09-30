<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ProductTypePayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le nom du type est obligatoire.')]
        #[Assert\Length(max: 100)]
        public string $name = '',
        #[Assert\Regex(pattern: '/^#[0-9a-fA-F]{6}$/', message: 'Choisissez une couleur.')]
        public ?string $color = null,
        #[Assert\Regex(pattern: '/^\s*[A-Za-z0-9]{1,8}\s*$|^\s*$/', message: 'Le code fait 1 à 8 lettres ou chiffres.')]
        public ?string $code = null,
    ) {
    }
}
