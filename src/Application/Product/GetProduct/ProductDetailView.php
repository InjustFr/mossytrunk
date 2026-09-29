<?php

declare(strict_types=1);

namespace App\Application\Product\GetProduct;

use App\Application\Product\ListProducts\ProductView;
use App\Application\Stock\StockItemView;

final readonly class ProductDetailView
{
    /**
     * @param list<StockItemView>                                                                                                                              $stock
     * @param list<array{date: string, kind: string, variant: ?string, quantity: int, cost: int, link: ?string, label: ?string}>                            $movements
     * @param list<array{id: string, price: int, since: string, sinceDay: string}>                                                                                                          $priceHistory
     * @param array{id: string, name: string}|null                                                                                                            $design
     */
    public function __construct(
        public ProductView $product,
        public int $unitsSoldEver,
        public int $salesEver,
        public array $stock,
        public array $movements,
        public array $priceHistory,
        public ?array $design,
    ) {
    }
}
