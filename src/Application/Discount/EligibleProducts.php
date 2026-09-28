<?php

declare(strict_types=1);

namespace App\Application\Discount;

use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\ProductType;
use App\Domain\Product\ProductTypeRepository;
use Symfony\Component\Uid\Ulid;

final readonly class EligibleProducts
{
    public function __construct(
        private ProductRepository $products,
        private ProductTypeRepository $types,
    ) {
    }

    /**
     * @param list<string> $typeIds
     *
     * @return list<ProductType>
     */
    public function resolveTypes(array $typeIds): array
    {
        return array_map(fn (string $id): ProductType => $this->types->get(Ulid::fromString($id)), array_values(array_unique($typeIds)));
    }

    /**
     * @param list<string> $productIds
     *
     * @return list<Product>
     */
    public function resolve(array $productIds): array
    {
        return array_map(fn (string $id): Product => $this->products->get(Ulid::fromString($id)), array_values(array_unique($productIds)));
    }
}
