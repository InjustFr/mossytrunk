<?php

declare(strict_types=1);

namespace App\Application\Product\ListProducts;

use App\Domain\Product\Product;

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
    ) {
    }

    public static function fromProduct(Product $product): self
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
        );
    }
}
