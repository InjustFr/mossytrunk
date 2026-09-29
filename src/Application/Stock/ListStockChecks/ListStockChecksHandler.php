<?php

declare(strict_types=1);

namespace App\Application\Stock\ListStockChecks;

use App\Domain\Product\ProductRepository;
use App\Domain\Stock\StockCheck;
use App\Domain\Stock\StockCheckRepository;
use Symfony\Component\Uid\Ulid;

final readonly class ListStockChecksHandler
{
    public function __construct(
        private StockCheckRepository $checks,
        private ProductRepository $products,
    ) {
    }

    /**
     * @return list<StockCheckView>
     */
    public function __invoke(string $eventId): array
    {
        $products = [];
        foreach ($this->products->all() as $product) {
            $products[(string) $product->id()] = $product;
        }

        return array_map(
            static fn (StockCheck $check): StockCheckView => StockCheckView::of($check, $products),
            $this->checks->ofEvent(Ulid::fromString($eventId)),
        );
    }
}
