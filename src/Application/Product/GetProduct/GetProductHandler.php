<?php

declare(strict_types=1);

namespace App\Application\Product\GetProduct;

use App\Application\Product\ListProducts\ProductView;
use App\Application\Product\ProductMovements;
use App\Application\Stock\ProductStock;
use App\Domain\Design\DesignRepository;
use App\Domain\Order\Order;
use App\Domain\Order\OrderRepository;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\SellingPriceChange;
use App\Domain\Reporting\SalesByProduct;
use App\Domain\Shared\DateRange;
use App\Domain\Stock\StockRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Ulid;

final readonly class GetProductHandler
{
    public function __construct(
        private ProductRepository $products,
        private OrderRepository $orders,
        private StockRepository $stock,
        private DesignRepository $designs,
        private ClockInterface $clock,
        private ProductMovements $movements,
    ) {
    }

    public function __invoke(string $productId): ProductDetailView
    {
        $product = $this->products->get(Ulid::fromString($productId));
        $year = DateRange::yearOf($this->clock->now());
        $orders = $this->orders->selling($product->id());
        $stockItems = $this->stock->ofProduct($product->id());
        $stock = ProductStock::of($product, $stockItems);
        $sales = array_values(array_filter($orders, static fn (Order $order): bool => !$order->isRefunded()));
        $ever = SalesByProduct::of($sales)->forProduct($product->id());
        $design = $this->designs->findByProduct($product->id());

        return new ProductDetailView(
            ProductView::fromProduct($product, $year, SalesByProduct::of(array_values(array_filter($sales, static fn (Order $order): bool => $order->isPlacedIn($year))))->forProduct($product->id()), $stock, $design?->collection()),
            $ever->quantity ?? 0,
            $ever?->sales->amount() ?? 0,
            $stock->items,
            $this->movements->of($product),
            array_reverse(array_map(static fn (SellingPriceChange $change): array => [
                'id' => (string) $change->id(),
                'price' => $change->price()->amount(),
                'since' => $change->since()->format(\DateTimeInterface::ATOM),
                'sinceDay' => $change->since()->setTimezone(new \DateTimeZone(DateRange::TIMEZONE))->format('Y-m-d'),
            ], $product->priceHistory())),
            null === $design ? null : ['id' => (string) $design->id(), 'name' => $design->name()],
        );
    }
}
