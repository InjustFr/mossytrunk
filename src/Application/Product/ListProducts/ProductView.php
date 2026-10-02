<?php

declare(strict_types=1);

namespace App\Application\Product\ListProducts;

use App\Application\Stock\ProductStock;
use App\Application\Stock\StockItemView;
use App\Domain\Design\DesignCollection;
use App\Domain\Product\Product;
use App\Domain\Product\SellingPriceChange;
use App\Domain\Reporting\ProductSales;
use App\Domain\Shared\DateRange;

final readonly class ProductView
{
    /**
     * @param list<string>                                                                $variants
     * @param list<string>                                                                $activeVariants
     * @param list<array{variant: ?string, onHand: int, low: bool, negative: bool}>       $stock
     * @param array<string, int>                                                          $channelPrices
     * @param list<array{price: int, sinceDay: string}>                                   $priceHistory
     * @param array{units: int, turnover: int, stockCost: int, urssaf: int, revenue: int} $potential
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
        public ?string $collectionId,
        public ?string $collectionName,
        public array $channelPrices,
        public array $priceHistory,
        public string $kind,
        public array $potential,
    ) {
    }

    public static function fromProduct(Product $product, int $salesYear, ?ProductSales $sales, ProductStock $stock, ?DesignCollection $collection): self
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
            null === $collection ? null : (string) $collection->id(),
            $collection?->name(),
            self::channelPricesOf($product),
            array_map(static fn (SellingPriceChange $change): array => [
                'price' => $change->price()->amount(),
                'sinceDay' => $change->since()->setTimezone(new \DateTimeZone(DateRange::TIMEZONE))->format('Y-m-d'),
            ], $product->priceHistory()),
            $product->kind()->value,
            $stock->potential->toArray(),
        );
    }

    /**
     * @return array<string, int>
     */
    private static function channelPricesOf(Product $product): array
    {
        $prices = [];
        foreach ($product->channelPrices() as $price) {
            $prices[(string) $price->channel()->id()] = $price->price()->amount();
        }

        return $prices;
    }
}
