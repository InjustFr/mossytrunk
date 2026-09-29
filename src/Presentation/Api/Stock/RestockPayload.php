<?php

declare(strict_types=1);

namespace App\Presentation\Api\Stock;

use App\Application\Stock\Restock\Restock;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class RestockPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Choisissez un produit.')]
        #[Assert\Ulid(message: 'Produit invalide.')]
        public string $productId = '',
        public ?string $variant = null,
        #[Assert\Positive(message: 'La quantité doit être d\'au moins 1.')]
        public int $quantity = 1,
        #[Assert\PositiveOrZero(message: 'Le prix payé ne peut pas être négatif.')]
        public int $totalPaid = 0,
    ) {
    }

    public function toCommand(): Restock
    {
        return new Restock($this->productId, $this->variant, $this->quantity, $this->totalPaid);
    }
}
