<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\MoveVariant\MoveVariant;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class MoveVariantPayload
{
    public function __construct(
        public ?string $variant = null,
        #[Assert\Ulid(message: 'Produit invalide.')]
        public ?string $targetProductId = null,
        #[Assert\Length(max: 255)]
        public ?string $newProductName = null,
        #[Assert\Length(max: 255)]
        public ?string $targetVariant = null,
    ) {
    }

    #[Assert\IsTrue(message: 'Choisissez le produit de destination ou nommez le nouveau produit.')]
    public function isTargetChosen(): bool
    {
        return (null !== $this->targetProductId) !== (null !== $this->newProductName && '' !== trim($this->newProductName));
    }

    public function toCommand(string $productId): MoveVariant
    {
        return new MoveVariant($productId, $this->variant, $this->targetProductId, $this->newProductName, $this->targetVariant);
    }
}
