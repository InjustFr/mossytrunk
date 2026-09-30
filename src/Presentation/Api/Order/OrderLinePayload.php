<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Application\Order\RequestedLine;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class OrderLinePayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'product.required')]
        #[Assert\Ulid(message: 'product.invalid')]
        public string $productId = '',
        public ?string $variant = null,
        #[Assert\Positive(message: 'quantity.atLeastOne')]
        public int $quantity = 1,
    ) {
    }

    public function toRequestedLine(): RequestedLine
    {
        return new RequestedLine($this->productId, $this->variant, $this->quantity);
    }
}
