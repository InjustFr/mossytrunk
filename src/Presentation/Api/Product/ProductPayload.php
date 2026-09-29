<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Domain\Product\Product;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request body for creating or updating a product. Prices are integer cents; the reference is generated.
 */
final readonly class ProductPayload
{
    /**
     * @param list<string> $variants
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
        #[Assert\Length(max: 255)]
        public string $name = '',
        #[Assert\PositiveOrZero(message: 'Le prix de vente ne peut pas être négatif.')]
        public int $sellingPrice = 0,
        #[Assert\All([new Assert\Type('string'), new Assert\NotBlank(message: 'Une variante ne peut pas être vide.')])]
        #[Assert\Unique(message: 'Les variantes doivent être uniques.')]
        public array $variants = [],
        #[Assert\Ulid(message: 'Type invalide.')]
        public ?string $typeId = null,
        #[Assert\PositiveOrZero(message: 'Le seuil de stock bas ne peut pas être négatif.')]
        public int $lowStockThreshold = Product::DEFAULT_LOW_STOCK_THRESHOLD,
    ) {
    }
}
