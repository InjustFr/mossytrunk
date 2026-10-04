<?php

declare(strict_types=1);

namespace App\Application\Stock;

use App\Domain\Product\Product;
use App\Domain\Reporting\StockPotential;
use App\Domain\Shared\Money;
use App\Domain\Stock\StockItem;

final readonly class ProductStock
{
    /**
     * @param list<StockItemView> $items
     */
    public function __construct(
        public array $items,
        public int $onHand,
        public bool $low,
        public bool $negative,
        public Money $unitCost,
        public StockPotential $potential,
    ) {
    }

    /**
     * @param list<StockItem> $stockItems
     */
    public static function of(Product $product, array $stockItems): self
    {
        $items = [];
        $value = Money::zero();
        $units = 0;
        foreach ($product->hasVariants() ? $product->variants() : [null] as $variant) {
            $stockItem = array_find($stockItems, static fn (StockItem $item): bool => $item->variant() === $variant);
            $items[] = StockItemView::of($variant, $stockItem, $product->lowStockThreshold());
            if (null !== $stockItem && $stockItem->onHand() > 0) {
                $value = $value->add($stockItem->remainingValue());
                $units += $stockItem->onHand();
            }
        }

        return new self(
            $items,
            array_sum(array_map(static fn (StockItemView $item): int => $item->onHand, $items)),
            array_any($items, static fn (StockItemView $item): bool => $item->low),
            array_any($items, static fn (StockItemView $item): bool => $item->negative),
            0 === $units || $value->isZero() ? $product->buyingPrice() : $value->prorate(1, $units),
            StockPotential::of($product, $stockItems),
        );
    }
}
