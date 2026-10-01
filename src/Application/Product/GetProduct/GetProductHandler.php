<?php

declare(strict_types=1);

namespace App\Application\Product\GetProduct;

use App\Application\Integration\Connectors;
use App\Application\Product\ListProducts\ProductView;
use App\Application\Stock\ProductStock;
use App\Domain\Design\DesignRepository;
use App\Domain\Order\Order;
use App\Domain\Order\OrderRepository;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\SellingPriceChange;
use App\Domain\Reporting\SalesByProduct;
use App\Domain\Shared\DateRange;
use App\Domain\Stock\LotOrigin;
use App\Domain\Stock\StockCheckRepository;
use App\Domain\Stock\StockItem;
use App\Domain\Stock\StockLot;
use App\Domain\Stock\StockRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Ulid;

final readonly class GetProductHandler
{
    public function __construct(
        private ProductRepository $products,
        private OrderRepository $orders,
        private StockRepository $stock,
        private StockCheckRepository $checks,
        private DesignRepository $designs,
        private ClockInterface $clock,
        private Connectors $connectors,
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
            $this->movements($product->id(), $orders, $stockItems),
            array_reverse(array_map(static fn (SellingPriceChange $change): array => [
                'id' => (string) $change->id(),
                'price' => $change->price()->amount(),
                'since' => $change->since()->format(\DateTimeInterface::ATOM),
                'sinceDay' => $change->since()->setTimezone(new \DateTimeZone(DateRange::TIMEZONE))->format('Y-m-d'),
            ], $product->priceHistory())),
            null === $design ? null : ['id' => (string) $design->id(), 'name' => $design->name()],
        );
    }

    /**
     * @param list<Order>     $orders
     * @param list<StockItem> $stockItems
     *
     * @return list<array{date: string, kind: string, variant: ?string, quantity: int, cost: int, link: ?string, label: ?string}>
     */
    private function movements(Ulid $productId, array $orders, array $stockItems): array
    {
        $movements = [];
        foreach ($stockItems as $item) {
            foreach ($item->lots() as $lot) {
                $movements[] = [
                    'date' => $lot->receivedAt()->format(\DateTimeInterface::ATOM),
                    'kind' => $lot->origin()->value,
                    'variant' => $item->variant(),
                    'quantity' => $lot->quantity(),
                    'cost' => $lot->totalCost()->amount(),
                    'link' => $this->lotLink($lot),
                    'label' => null,
                ];
            }
        }
        foreach ($orders as $order) {
            foreach ($order->lines() as $line) {
                if ($line->productId()?->equals($productId) ?? false) {
                    $movements[] = [
                        'date' => $order->placedAt()->format(\DateTimeInterface::ATOM),
                        'kind' => 'sale',
                        'variant' => $line->variant(),
                        'quantity' => -$line->quantity(),
                        'cost' => $line->cost()->amount(),
                        'link' => '/orders/'.$order->id(),
                        'label' => $order->event()?->name() ?? $this->connectors->labelOf($order->source()),
                    ];
                }
            }
        }
        foreach ($this->checks->counting($productId) as $check) {
            foreach ($check->lines() as $line) {
                if ($line->productId()->equals($productId) && $line->missing() > 0) {
                    $movements[] = [
                        'date' => $check->checkedAt()->format(\DateTimeInterface::ATOM),
                        'kind' => 'loss',
                        'variant' => $line->variant(),
                        'quantity' => -$line->missing(),
                        'cost' => $line->lossCost()->amount(),
                        'link' => '/events/'.$check->event()->id(),
                        'label' => $check->event()->name(),
                    ];
                }
            }
        }

        usort($movements, static fn (array $a, array $b): int => $b['date'] <=> $a['date']);

        return $movements;
    }

    private function lotLink(StockLot $lot): ?string
    {
        return match (true) {
            null === $lot->sourceId() => null,
            LotOrigin::Return === $lot->origin() => '/orders/'.$lot->sourceId(),
            default => '/supplier-orders/'.$lot->sourceId(),
        };
    }
}
