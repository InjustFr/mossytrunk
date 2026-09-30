<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\DeleteProducts\DeleteProducts;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class DeleteProductsPayload
{
    /**
     * @param list<string> $productIds
     */
    public function __construct(
        #[Assert\Count(min: 1, minMessage: 'products.selectAtLeastOne')]
        #[Assert\All([new Assert\Ulid()])]
        public array $productIds = [],
    ) {
    }

    public function toCommand(): DeleteProducts
    {
        return new DeleteProducts($this->productIds);
    }
}
