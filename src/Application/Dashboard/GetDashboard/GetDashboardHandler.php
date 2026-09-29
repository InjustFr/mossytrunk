<?php

declare(strict_types=1);

namespace App\Application\Dashboard\GetDashboard;

use App\Application\Stock\ProductStock;
use App\Domain\Event\Event;
use App\Domain\Event\EventRepository;
use App\Domain\Order\Order;
use App\Domain\Order\OrderRepository;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use App\Domain\Reporting\EventResult;
use App\Domain\Reporting\MonthlyResults;
use App\Domain\Reporting\ProductSales;
use App\Domain\Reporting\SalesByProduct;
use App\Domain\Shared\DateRange;
use App\Domain\Stock\StockRepository;

/**
 * Results per month of a year, and per year, with the year's events and best sellers. See docs/business/dashboard.md.
 */
final readonly class GetDashboardHandler
{
    private const int TOP_PRODUCTS = 5;

    public function __construct(
        private OrderRepository $orders,
        private EventRepository $events,
        private ProductRepository $products,
        private StockRepository $stock,
    ) {
    }

    /**
     * @param int|null $year defaults to the current year
     */
    public function __invoke(?int $year = null): DashboardView
    {
        $orders = $this->orders->list();
        $events = $this->events->all();
        $results = MonthlyResults::of($orders, $events);
        $year ??= DateRange::yearOf(new \DateTimeImmutable('now'));

        $years = $results->years();
        if (!\in_array($year, $years, true)) {
            $years[] = $year;
            rsort($years);
        }

        $months = [];
        for ($month = 1; $month <= 12; ++$month) {
            $months[] = ['month' => $month] + $results->month($year, $month)->toArray();
        }

        $sales = SalesByProduct::of(array_values(array_filter($orders, static fn (Order $order): bool => $order->isPlacedIn($year))))->ranked();
        $typeNames = $this->typeNamesOf($sales);
        $products = $this->products->all();
        $stocks = $this->stocksOf($products);

        return new DashboardView(
            $year,
            $years,
            $months,
            $results->year($year)->toArray(),
            array_map(static fn (int $y): array => ['year' => $y] + $results->year($y)->toArray(), $results->years()),
            $this->eventsOf($year, $events, $orders),
            array_map(static fn (ProductSales $product): array => [
                'id' => (string) $product->productId,
                'name' => $product->productName,
                'typeName' => $typeNames[(string) $product->productId] ?? null,
                'quantity' => $product->quantity,
                'sales' => $product->sales->amount(),
            ], \array_slice($sales, 0, self::TOP_PRODUCTS)),
            $this->salesByType($sales, $typeNames),
            \count(array_filter($products, static fn (Product $product): bool => $product->buyingPrice()->isZero())),
            \count(array_filter($stocks, static fn (ProductStock $stock): bool => $stock->low && !$stock->negative)),
            \count(array_filter($stocks, static fn (ProductStock $stock): bool => $stock->negative)),
        );
    }

    /**
     * @param list<Product> $products
     *
     * @return list<ProductStock>
     */
    private function stocksOf(array $products): array
    {
        $byProduct = [];
        foreach ($this->stock->all() as $item) {
            $byProduct[(string) $item->product()->id()][] = $item;
        }

        return array_map(static fn (Product $product): ProductStock => ProductStock::of($product, $byProduct[(string) $product->id()] ?? []), $products);
    }

    /**
     * @param list<Event> $events
     * @param list<Order> $orders
     *
     * @return list<array{id: string, name: string, startDate: string, turnover: int, result: int}>
     */
    private function eventsOf(int $year, array $events, array $orders): array
    {
        $ordersByEvent = [];
        foreach ($orders as $order) {
            $ordersByEvent[(string) $order->event()->id()][] = $order;
        }

        $rows = [];
        foreach ($events as $event) {
            if (!$event->startsIn($year)) {
                continue;
            }
            $result = EventResult::of($event, $ordersByEvent[(string) $event->id()] ?? []);
            $rows[] = [
                'id' => (string) $event->id(),
                'name' => $event->name(),
                'startDate' => $event->period()->start()->format('Y-m-d'),
                'turnover' => $result->turnover->amount(),
                'result' => $result->result->amount(),
            ];
        }
        usort($rows, static fn (array $a, array $b): int => $b['result'] <=> $a['result']);

        return $rows;
    }

    /**
     * @param list<ProductSales> $sales
     *
     * @return array<string, string|null> type name by product id
     */
    private function typeNamesOf(array $sales): array
    {
        $typeNames = [];
        foreach ($this->products->findByIds(array_map(static fn (ProductSales $product) => $product->productId, $sales)) as $product) {
            $typeNames[(string) $product->id()] = $product->type()?->name();
        }

        return $typeNames;
    }

    /**
     * @param list<ProductSales>         $sales
     * @param array<string, string|null> $typeNames
     *
     * @return list<array{name: ?string, quantity: int, sales: int}>
     */
    private function salesByType(array $sales, array $typeNames): array
    {
        $types = [];
        foreach ($sales as $product) {
            $name = $typeNames[(string) $product->productId] ?? null;
            $key = $name ?? '';
            $types[$key] ??= ['name' => $name, 'quantity' => 0, 'sales' => 0];
            $types[$key]['quantity'] += $product->quantity;
            $types[$key]['sales'] += $product->sales->amount();
        }
        $types = array_values($types);
        usort($types, static fn (array $a, array $b): int => $b['sales'] <=> $a['sales']);

        return $types;
    }
}
