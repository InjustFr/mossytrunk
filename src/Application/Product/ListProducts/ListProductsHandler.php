<?php

declare(strict_types=1);

namespace App\Application\Product\ListProducts;

use App\Application\Reporting\ProductSalesLedger;
use App\Application\Stock\ProductStock;
use App\Domain\Design\DesignCollectionRepository;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use App\Domain\Reporting\SalesByProduct;
use App\Domain\Shared\DateRange;
use App\Domain\Stock\StockRepository;
use Psr\Clock\ClockInterface;

final readonly class ListProductsHandler
{
    public function __construct(
        private ProductRepository $products,
        private ProductSalesLedger $sales,
        private StockRepository $stock,
        private DesignCollectionRepository $collections,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return list<ProductView> with their sales of the current year (Europe/Paris)
     */
    public function __invoke(): array
    {
        $year = DateRange::yearOf($this->clock->now());
        $sales = SalesByProduct::of($this->sales->within(DateRange::year($year)));

        $stockByProduct = [];
        foreach ($this->stock->all() as $item) {
            $stockByProduct[(string) $item->product()->id()][] = $item;
        }

        $collections = $this->collections->byProduct();

        return array_map(
            static fn (Product $product): ProductView => ProductView::fromProduct(
                $product,
                $year,
                $sales->forProduct($product->id()),
                ProductStock::of($product, $stockByProduct[(string) $product->id()] ?? []),
                $collections[(string) $product->id()] ?? null,
            ),
            $this->products->catalogue(),
        );
    }
}
