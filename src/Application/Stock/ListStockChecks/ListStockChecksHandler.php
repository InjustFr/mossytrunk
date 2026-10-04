<?php

declare(strict_types=1);

namespace App\Application\Stock\ListStockChecks;

use App\Domain\Product\ProductRepository;
use App\Domain\Stock\StockCheck;
use App\Domain\Stock\StockCheckLine;
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
        $checks = $this->checks->ofEvent(Ulid::fromString($eventId));
        $products = $this->products->findByIds(array_merge(...array_map(
            static fn (StockCheck $check): array => array_map(static fn (StockCheckLine $line): Ulid => $line->productId(), $check->lines()),
            $checks,
        )));

        return array_map(static fn (StockCheck $check): StockCheckView => StockCheckView::of($check, $products), $checks);
    }
}
