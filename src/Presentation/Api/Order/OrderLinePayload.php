<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Application\Order\RequestedLine;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class OrderLinePayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Choisissez un produit.')]
        #[Assert\Ulid(message: 'Produit invalide.')]
        public string $productId = '',
        public ?string $variant = null,
        #[Assert\Positive(message: 'La quantité doit être d\'au moins 1.')]
        public int $quantity = 1,
    ) {
    }

    public function toRequestedLine(): RequestedLine
    {
        return new RequestedLine($this->productId, $this->variant, $this->quantity);
    }
}
