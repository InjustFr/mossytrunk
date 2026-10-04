<?php

declare(strict_types=1);

namespace App\Application\Stock;

use App\Domain\Event\Event;
use App\Domain\Order\Order;
use App\Domain\Order\OrderedItem;
use App\Domain\Order\OrderLine;
use App\Domain\Order\OrderSupply;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use App\Domain\Shared\Money;
use App\Domain\Stock\StockCheck;
use App\Domain\Stock\StockCheckRepository;
use App\Domain\Stock\StockItem;
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
     * @param list<OrderedItem>     $items
     * @param list<StockCheck>|null $checks
     *
     * @return list<OrderedItem>
     */
    public function withdraw(?Event $event, array $items, ?array $checks = null): array
    {
        $checks ??= $this->checksAt($event);

        return array_map(function (OrderedItem $ordered) use ($checks): OrderedItem {
            $item = $ordered->item;
            $productId = $item->productId;
            if (null === $productId) {
                return $ordered->costing(Money::zero());
            }
            $quantity = $ordered->quantity;
            $cost = Money::zero();

            foreach ($checks as $check) {
                $explained = $check->explain($productId, $item->variant, $quantity);
                $quantity -= $explained->quantity;
                $cost = $cost->add($explained->cost);
            }

            if ($quantity > 0) {
                $stock = $this->stock->for($this->products->get($productId), $item->variant);
                $cost = $cost->add($stock->withdraw($quantity, $item->buyingPrice));
            }

            return $ordered->costing($cost);
        }, $items);
    }

    /**
     * @return list<StockCheck>
     */
    public function checksAt(?Event $event): array
    {
        return null === $event ? [] : $this->checks->ofEvent($event->id());
    }

    public function takeBack(Order $order, \DateTimeImmutable $returnedAt): void
    {
        foreach ($this->soldStock($order) as [$line, $stock]) {
            $stock->takeBack($line->quantity(), $line->cost(), $returnedAt, $order->id());
        }
    }

    public function cancelSale(Order $order): void
    {
        foreach ($this->soldStock($order) as [$line, $stock]) {
            $stock->cancelReturnOf($order->id());
            $stock->cancelWithdrawal($line->quantity());
        }
        foreach ($order->supplies() as $supply) {
            $this->giveBack($supply);
        }
    }

    public function giveBack(OrderSupply $supply): void
    {
        $product = $this->products->find($supply->productId());
        if (null !== $product && $product->sells($supply->variant())) {
            $this->stock->for($product, $supply->variant())->cancelWithdrawal($supply->quantity());
        }
    }

    /**
     * @return list<array{OrderLine, StockItem}>
     */
    private function soldStock(Order $order): array
    {
        $sold = [];
        foreach ($order->lines() as $line) {
            $productId = $line->productId();
            $product = null === $productId ? null : $this->products->find($productId);
            if (null !== $product && $product->sells($line->variant())) {
                $sold[] = [$line, $this->stock->for($product, $line->variant())];
            }
        }

        return $sold;
    }

    public function followVariants(Product $product): void
    {
        foreach ($this->stock->ofProduct($product->id()) as $item) {
            if ($product->sells($item->variant())) {
                continue;
            }
            if ($this->switchedBetweenUniqueAndVariants($product, $item->variant())) {
                $this->stock->for($product, $product->variants()[0] ?? null)->absorb($item);
            }
            $this->stock->remove($item);
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

    private function switchedBetweenUniqueAndVariants(Product $product, ?string $variant): bool
    {
        return (null === $variant) === $product->hasVariants();
    }
}
