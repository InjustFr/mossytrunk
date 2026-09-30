<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\BatchUpdateProducts\BatchUpdateProducts;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class BatchProductsPayload
{
    /**
     * @param list<string> $productIds
     * @param list<string> $addVariants
     * @param list<string> $removeVariants
     */
    public function __construct(
        #[Assert\Count(min: 1, minMessage: 'products.selectAtLeastOne')]
        #[Assert\All([new Assert\Ulid()])]
        public array $productIds = [],
        #[Assert\PositiveOrZero(message: 'sellingPrice.negative')]
        public ?int $sellingPrice = null,
        public bool $changeType = false,
        #[Assert\Ulid(message: 'productType.invalid')]
        public ?string $typeId = null,
        #[Assert\All([new Assert\Type('string'), new Assert\NotBlank(message: 'variant.blank')])]
        public array $addVariants = [],
        #[Assert\All([new Assert\Type('string')])]
        public array $removeVariants = [],
    ) {
    }

    public function toCommand(): BatchUpdateProducts
    {
        return new BatchUpdateProducts($this->productIds, $this->sellingPrice, $this->changeType, $this->typeId, $this->addVariants, $this->removeVariants);
    }
}
