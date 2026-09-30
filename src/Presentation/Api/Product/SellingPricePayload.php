<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SellingPricePayload
{
    public function __construct(
        #[Assert\PositiveOrZero(message: 'sellingPrice.negative')]
        public int $price = 0,
        #[Assert\NotBlank(message: 'date.required')]
        #[Assert\Date(message: 'date.invalid')]
        public string $since = '',
    ) {
    }
}
