<?php

declare(strict_types=1);

namespace App\Application\Product\ListProducts;

use App\Application\Stock\ProductStock;
use App\Application\Stock\StockItemView;
use App\Domain\Product\Product;
use App\Domain\Reporting\ProductSales;

final readonly class ProductView
{
    /**
     * @param list<string>                                                          $variants
     * @param list<string>                                                          $activeVariants
     * @param list<array{variant: ?string, onHand: int, low: bool, negative: bool}> $stock
     */
    public function __construct(
        public string $id,
        public string $reference,
        public string $name,
        public string $displayName,
        public string $typeId,
        public string $typeName,
        public int $sellingPrice,
        public int $buyingPrice,
        public array $variants,
        public int $salesYear,
        public int $unitsSold,
        public int $sales,
        public int $lowStockThreshold,
        public int $onHand,
        public bool $lowStock,
        public bool $negativeStock,
        public int $stockUnitCost,
        public array $stock,
        public array $activeVariants,
        public bool $archived,
        public bool $archivedItself,
    ) {
    }

    public static function fromProduct(Product $product, int $salesYear, ?ProductSales $sales, ProductStock $stock): self
    {
        return new self(
            (string) $product->id(),
            $product->reference(),
            $product->name(),
            $product->displayName(),
            (string) $product->type()->id(),
            $product->type()->name(),
            $product->sellingPrice()->amount(),
            $product->buyingPrice()->amount(),
            $product->variants(),
            $salesYear,
            $sales->quantity ?? 0,
            $sales?->sales->amount() ?? 0,
            $product->lowStockThreshold(),
            $stock->onHand,
            $stock->low,
            $stock->negative,
            $stock->unitCost->amount(),
            array_map(static fn (StockItemView $item): array => [
                'variant' => $item->variant,
                'onHand' => $item->onHand,
                'low' => $item->low,
                'negative' => $item->negative,
            ], $stock->items),
            $product->activeVariants(),
            $product->isArchived(),
            $product->isArchivedItself(),
        );
    }
}
