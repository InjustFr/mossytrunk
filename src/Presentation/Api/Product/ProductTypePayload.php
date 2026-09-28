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
    ) {
    }
}
