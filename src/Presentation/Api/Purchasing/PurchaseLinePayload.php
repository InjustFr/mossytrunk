<?php

declare(strict_types=1);

namespace App\Presentation\Api\Purchasing;

use App\Application\Purchasing\PurchaseLine;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class PurchaseLinePayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Choisissez un produit.')]
        #[Assert\Ulid(message: 'Produit invalide.')]
        public string $productId = '',
        public ?string $variant = null,
        #[Assert\Positive(message: 'La quantité doit être d\'au moins 1.')]
        public int $quantity = 1,
        #[Assert\PositiveOrZero(message: 'Le prix payé ne peut pas être négatif.')]
        public int $totalPrice = 0,
    ) {
    }

    public function toLine(): PurchaseLine
    {
        return new PurchaseLine($this->productId, $this->variant, $this->quantity, $this->totalPrice);
    }
}
