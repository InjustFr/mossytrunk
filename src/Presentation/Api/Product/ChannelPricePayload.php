<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChannelPricePayload
{
    public function __construct(
        #[Assert\PositiveOrZero(message: 'sellingPrice.negative')]
        public ?int $price = null,
    ) {
    }
}
