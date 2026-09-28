<?php

declare(strict_types=1);

namespace App\Application\Discount;

use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use Symfony\Component\Uid\Ulid;

final readonly class EligibleProducts
{
    public function __construct(private ProductRepository $products)
    {
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
