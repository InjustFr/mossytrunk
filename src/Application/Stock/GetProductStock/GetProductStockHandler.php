<?php

declare(strict_types=1);

namespace App\Application\Stock\GetProductStock;

use App\Application\Stock\ProductStock;
use App\Application\Stock\StockItemView;
use App\Domain\Product\ProductRepository;
use App\Domain\Stock\StockRepository;
use Symfony\Component\Uid\Ulid;

final readonly class GetProductStockHandler
{
    public function __construct(
        private ProductRepository $products,
        private StockRepository $stock,
    ) {
    }

    /**
     * @return list<StockItemView>
     */
    public function __invoke(string $productId): array
    {
        $product = $this->products->get(Ulid::fromString($productId));

        return ProductStock::of($product, $this->stock->ofProduct($product->id()))->items;
    }
}
