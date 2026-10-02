<?php

declare(strict_types=1);

namespace App\Application\Accounting\GetStockPotential;

use App\Domain\Product\ProductRepository;
use App\Domain\Reporting\StockPotential;
use App\Domain\Stock\StockRepository;

final readonly class GetStockPotentialHandler
{
    public function __construct(
        private ProductRepository $products,
        private StockRepository $stock,
    ) {
    }

    public function __invoke(): StockPotential
    {
        $stockByProduct = [];
        foreach ($this->stock->all() as $item) {
            $stockByProduct[(string) $item->product()->id()][] = $item;
        }

        $potential = StockPotential::none();
        foreach ($this->products->articles() as $product) {
            $potential = $potential->add(StockPotential::of($product, $stockByProduct[(string) $product->id()] ?? []));
        }

        return $potential;
    }
}
