<?php

declare(strict_types=1);

namespace App\Presentation\Api\Stock;

use App\Application\Stock\TakeStockCheck\CountedItem;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CountedItemPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Choisissez un produit.')]
        #[Assert\Ulid(message: 'Produit invalide.')]
        public string $productId = '',
        public ?string $variant = null,
        #[Assert\PositiveOrZero(message: 'La quantité comptée ne peut pas être négative.')]
        public int $counted = 0,
    ) {
    }

    public function toCountedItem(): CountedItem
    {
        return new CountedItem($this->productId, $this->variant, $this->counted);
    }
}
