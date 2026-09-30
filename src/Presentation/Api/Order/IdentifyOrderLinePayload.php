<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class IdentifyOrderLinePayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'product.required')]
        #[Assert\Ulid(message: 'product.required')]
        public string $productId = '',
        public ?string $variant = null,
    ) {
    }
}
