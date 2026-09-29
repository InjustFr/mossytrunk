<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SellingPricePayload
{
    public function __construct(
        #[Assert\PositiveOrZero(message: 'Le prix de vente ne peut pas être négatif.')]
        public int $price = 0,
        #[Assert\NotBlank(message: 'La date est obligatoire.')]
        #[Assert\Date(message: 'Date invalide.')]
        public string $since = '',
    ) {
    }
}
