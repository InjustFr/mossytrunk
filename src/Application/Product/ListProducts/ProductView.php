<?php

declare(strict_types=1);

namespace App\Application\Product\ListProducts;

use App\Domain\Product\Product;
use App\Domain\Reporting\ProductSales;

final readonly class ProductView
{
    /**
     * @param list<string> $variants
     */
    public function __construct(
        public string $id,
        public string $reference,
        public string $name,
        public string $displayName,
        public ?string $typeId,
        public ?string $typeName,
        public int $sellingPrice,
        public int $buyingPrice,
        public array $variants,
        public int $salesYear,
        public int $unitsSold,
        public int $sales,
    ) {
    }

    public static function fromProduct(Product $product, int $salesYear, ?ProductSales $sales): self
    {
        return new self(
            (string) $product->id(),
            $product->reference(),
            $product->name(),
            $product->displayName(),
            null === $product->type() ? null : (string) $product->type()->id(),
            $product->type()?->name(),
            $product->sellingPrice()->amount(),
            $product->buyingPrice()->amount(),
            $product->variants(),
            $salesYear,
            $sales?->quantity ?? 0,
            $sales?->sales->amount() ?? 0,
        );
    }
}
