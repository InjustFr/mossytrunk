<?php

declare(strict_types=1);

namespace App\Application\Stock;

use App\Domain\Event\Event;
use App\Domain\Order\Order;
use App\Domain\Order\OrderedItem;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use App\Domain\Shared\Money;
use App\Domain\Stock\StockCheckRepository;
use App\Domain\Stock\StockRepository;

final readonly class StockKeeper
{
    public function __construct(
        private StockRepository $stock,
        private StockCheckRepository $checks,
        private ProductRepository $products,
    ) {
    }

    /**
     * @param list<OrderedItem> $items
     *
     * @return list<OrderedItem>
     */
    public function withdraw(?Event $event, array $items): array
    {
        $checks = null === $event ? [] : $this->checks->ofEvent($event->id());

        return array_map(function (OrderedItem $ordered) use ($checks): OrderedItem {
            $item = $ordered->item;
            $quantity = $ordered->quantity;
            $cost = Money::zero();

            foreach ($checks as $check) {
                $explained = $check->explain($item->productId, $item->variant, $quantity);
                $quantity -= $explained->quantity;
                $cost = $cost->add($explained->cost);
            }

            if ($quantity > 0) {
                $stock = $this->stock->for($this->products->get($item->productId), $item->variant);
                $cost = $cost->add($stock->withdraw($quantity, $item->buyingPrice));
            }

            return $ordered->costing($cost);
        }, $items);
    }

    public function putBack(Order $order): void
    {
        foreach ($order->lines() as $line) {
            $product = $this->products->findByIds([$line->productId()])[0] ?? null;
            if (null === $product || !$this->stillSells($product, $line->variant())) {
                continue;
            }

            $this->stock->for($product, $line->variant())->putBack($line->quantity(), $line->cost(), $order->placedAt());
        }
    }

    public function forgetUnsold(Product $product): void
    {
        foreach ($this->stock->ofProduct($product->id()) as $item) {
            if (!$this->stillSells($product, $item->variant())) {
                $this->stock->remove($item);
            }
        }
    }

    public function move(Product $source, ?string $variant, Product $target, ?string $targetVariant): void
    {
        $from = $this->stock->find($source->id(), $variant);
        if (null === $from) {
            return;
        }

        $this->stock->for($target, $targetVariant)->absorb($from);
        $this->stock->remove($from);
    }

    private function stillSells(Product $product, ?string $variant): bool
    {
        return null === $variant ? !$product->hasVariants() : $product->hasVariant($variant);
    }
}
