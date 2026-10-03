<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use Symfony\Component\Uid\Ulid;

final readonly class SalesByProduct
{
    /**
     * @param array<string, ProductSales> $products keyed by product id
     */
    private function __construct(private array $products)
    {
    }

    /**
     * @param list<ProductSales> $sales one per product
     */
    public static function of(array $sales): self
    {
        $products = [];
        foreach ($sales as $product) {
            if (null !== $product->productId) {
                $products[(string) $product->productId] = $product;
            }
        }

        return new self($products);
    }

    public function forProduct(Ulid $productId): ?ProductSales
    {
        return $this->products[(string) $productId] ?? null;
    }

    /**
     * @return list<ProductSales> best sales first
     */
    public function ranked(): array
    {
        $ranked = array_values($this->products);
        usort($ranked, static fn (ProductSales $a, ProductSales $b): int => [$b->sales->amount(), $a->label] <=> [$a->sales->amount(), $b->label]);

        return $ranked;
    }
}
