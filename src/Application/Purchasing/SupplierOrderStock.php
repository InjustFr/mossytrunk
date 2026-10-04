<?php

declare(strict_types=1);

namespace App\Application\Purchasing;

use App\Application\Order\MissingCosts;
use App\Application\Order\RevisedLotCosts;
use App\Domain\Product\ProductRepository;
use App\Domain\Purchasing\SupplierOrder;
use App\Domain\Shared\Money;
use App\Domain\Stock\StockItem;
use App\Domain\Stock\StockRepository;

final readonly class SupplierOrderStock
{
    public function __construct(
        private ProductRepository $products,
        private StockRepository $stock,
        private MissingCosts $missingCosts,
        private RevisedLotCosts $revisedLotCosts,
    ) {
    }

    public function follow(SupplierOrder $order): void
    {
        $receivedAt = $order->receivedAt();
        if (null === $receivedAt) {
            return;
        }

        $restated = [];
        foreach ($order->lines() as $line) {
            $product = $this->products->find($line->productId());
            if (null === $product || !$product->sells($line->variant())) {
                continue;
            }
            $item = $this->stock->for($product, $line->variant());
            $previous = $item->lotFrom($order->id());
            $lot = $item->restate($order->id(), (int) $line->receivedQuantity(), $order->inEuros($line->landedCost()), $receivedAt);
            if (null !== $lot && $item->isLatestPurchase($lot)) {
                $product->bought($lot->unitCost());
            }
            $revision = $previous?->revisedAs($lot);
            if (null !== $revision) {
                $this->revisedLotCosts->follow($item, $revision);
            }
            $restated[] = $item;
        }
        $this->missingCosts->fill($restated);

        foreach ($this->stock->receivedFrom($order->id()) as $item) {
            if (!\in_array($item, $restated, true)) {
                $item->restate($order->id(), 0, Money::zero(), $receivedAt);
            }
        }
    }

    public function moveInto(SupplierOrder $absorbed, SupplierOrder $order): void
    {
        array_map(static fn (StockItem $item) => $item->moveLotsOf($absorbed->id(), $order->id()), $this->stock->receivedFrom($absorbed->id()));
        $this->follow($order);
    }
}
