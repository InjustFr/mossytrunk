<?php

declare(strict_types=1);

namespace App\Application\Product\GetProduct;

use App\Application\Product\ListProducts\ProductView;
use App\Application\Stock\ProductStock;
use App\Domain\Design\DesignRepository;
use App\Domain\Order\Order;
use App\Domain\Order\OrderRepository;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\SellingPriceChange;
use App\Domain\Reporting\SalesByProduct;
use App\Domain\Shared\DateRange;
use App\Domain\Stock\StockCheckRepository;
use App\Domain\Stock\StockItem;
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
    ) {
    }

    public function __invoke(string $productId): ProductDetailView
    {
        $product = $this->products->get(Ulid::fromString($productId));
        $year = DateRange::yearOf($this->clock->now());
        $orders = $this->orders->selling($product->id());
        $stockItems = $this->stock->ofProduct($product->id());
        $stock = ProductStock::of($product, $stockItems);
        $ever = SalesByProduct::of($orders)->forProduct($product->id());
        $design = $this->designs->findByProduct($product->id());

        return new ProductDetailView(
            ProductView::fromProduct($product, $year, SalesByProduct::of(array_values(array_filter($orders, static fn (Order $order): bool => $order->isPlacedIn($year))))->forProduct($product->id()), $stock),
            $ever?->quantity ?? 0,
            $ever?->sales->amount() ?? 0,
            $stock->items,
            $this->movements($product->id(), $orders, $stockItems),
            array_reverse(array_map(static fn (SellingPriceChange $change): array => [
                'price' => $change->price()->amount(),
                'since' => $change->since()->format(\DateTimeInterface::ATOM),
            ], $product->priceHistory())),
            null === $design ? null : ['id' => (string) $design->id(), 'name' => $design->name()],
        );
    }

    /**
     * @param list<Order>      $orders
     * @param list<StockItem>  $stockItems
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
                    'link' => null === $lot->sourceId() ? null : '/commandes-fournisseurs/'.$lot->sourceId(),
                    'label' => null,
                ];
            }
        }
        foreach ($orders as $order) {
            foreach ($order->lines() as $line) {
                if ($line->productId()->equals($productId)) {
                    $movements[] = [
                        'date' => $order->placedAt()->format(\DateTimeInterface::ATOM),
                        'kind' => 'sale',
                        'variant' => $line->variant(),
                        'quantity' => -$line->quantity(),
                        'cost' => $line->cost()->amount(),
                        'link' => '/commandes/'.$order->id(),
                        'label' => $order->event()->name(),
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
                        'link' => '/evenements/'.$check->event()->id(),
                        'label' => $check->event()->name(),
                    ];
                }
            }
        }

        usort($movements, static fn (array $a, array $b): int => $b['date'] <=> $a['date']);

        return $movements;
    }
}
