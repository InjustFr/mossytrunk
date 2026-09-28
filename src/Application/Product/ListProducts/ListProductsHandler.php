<?php

declare(strict_types=1);

namespace App\Application\Product\ListProducts;

use App\Domain\Product\ProductRepository;

final readonly class ListProductsHandler
{
    public function __construct(private ProductRepository $products)
    {
    }

    /**
     * @return list<ProductView>
     */
    public function __invoke(): array
    {
        return array_map(ProductView::fromProduct(...), $this->products->all());
    }
}
