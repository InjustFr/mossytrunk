<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Domain\Product\Product;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request body for creating or updating a product. Prices are integer cents; a blank reference is suggested at creation
 * and kept on update.
 */
final readonly class ProductPayload
{
    /**
     * @param list<string> $variants
     */
    public function __construct(
        #[Assert\NotBlank(message: 'name.required')]
        #[Assert\Length(max: 255)]
        public string $name = '',
        #[Assert\PositiveOrZero(message: 'sellingPrice.negative')]
        public int $sellingPrice = 0,
        #[Assert\All([new Assert\Type('string'), new Assert\NotBlank(message: 'variant.blank')])]
        #[Assert\Unique(message: 'variant.duplicate')]
        public array $variants = [],
        #[Assert\NotBlank(message: 'productType.required')]
        #[Assert\Ulid(message: 'productType.invalid')]
        public ?string $typeId = null,
        #[Assert\PositiveOrZero(message: 'product.lowStock.negative')]
        public int $lowStockThreshold = Product::DEFAULT_LOW_STOCK_THRESHOLD,
        #[Assert\Length(max: Product::REFERENCE_MAX_LENGTH, maxMessage: 'product.reference.tooLong')]
        public ?string $reference = null,
    ) {
    }
}
