<?php

declare(strict_types=1);

namespace App\Application\Product;

use App\Application\WorkspaceContext;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\ProductType;
use App\Domain\Shared\Money;

final readonly class NewProducts
{
    public function __construct(
        private ProductRepository $products,
        private ProductReferenceGenerator $references,
        private WorkspaceContext $workspace,
    ) {
    }

    /**
     * @param list<string> $variants
     */
    public function create(string $name, Money $sellingPrice, ProductType $type, array $variants = []): Product
    {
        $product = Product::create($this->workspace->current(), $this->references->generate($type, $name), $name, $sellingPrice, $type, $variants);
        $this->products->add($product);

        return $product;
    }
}
