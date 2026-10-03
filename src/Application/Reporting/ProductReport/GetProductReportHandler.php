<?php

declare(strict_types=1);

namespace App\Application\Reporting\ProductReport;

use App\Application\Product\ProductMovements;
use App\Application\Reporting\ReportPeriod;
use App\Domain\Discount\DiscountRule;
use App\Domain\Discount\DiscountRuleRepository;
use App\Domain\Order\OrderRepository;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use App\Domain\Shared\DateRange;
use App\Domain\Stock\StockRepository;
use Symfony\Component\Uid\Ulid;

final readonly class GetProductReportHandler
{
    public function __construct(
        private ProductRepository $products,
        private OrderRepository $orders,
        private StockRepository $stock,
        private DiscountRuleRepository $rules,
        private ProductMovements $movements,
        private ReportPeriod $periods,
    ) {
    }

    public function __invoke(string $productId, string $period): ProductReportView
    {
        $product = $this->products->get(Ulid::fromString($productId));
        $range = $this->periods->resolve($period);
        $months = array_fill_keys(ReportPeriod::months($range), ['units' => 0, 'gross' => 0, 'revenue' => 0, 'received' => 0, 'sold' => 0, 'lost' => 0, 'used' => 0, 'onHand' => 0]);

        foreach ($this->orders->selling($product->id()) as $order) {
            $month = ReportPeriod::monthOf($order->placedAt());
            if ($order->isRefunded() || !isset($months[$month])) {
                continue;
            }
            foreach ($order->lines() as $line) {
                if ($line->productId()?->equals($product->id()) ?? false) {
                    $months[$month]['units'] += $line->quantity();
                    $months[$month]['gross'] += $line->total()->amount();
                    $months[$month]['revenue'] += $line->revenue()->amount();
                }
            }
        }

        $onHand = array_sum(array_map(static fn ($item): int => $item->onHand(), $this->stock->ofProduct($product->id())));
        $flows = [];
        foreach ($this->movements->of($product) as $movement) {
            $month = ReportPeriod::monthOf(new \DateTimeImmutable($movement['date']));
            $flows[$month] ??= ['received' => 0, 'sold' => 0, 'lost' => 0, 'used' => 0, 'net' => 0];
            $flows[$month]['net'] += $movement['quantity'];
            $bucket = match ($movement['kind']) {
                'sale' => 'sold',
                'loss' => 'lost',
                'supply' => 'used',
                default => 'received',
            };
            $flows[$month][$bucket] += abs($movement['quantity']);
        }

        $level = $onHand;
        foreach ($flows as $month => $flow) {
            if ($month > array_key_last($months)) {
                $level -= $flow['net'];
            }
        }
        foreach (array_reverse($months, true) as $month => $figures) {
            $flow = $flows[$month] ?? ['received' => 0, 'sold' => 0, 'lost' => 0, 'used' => 0, 'net' => 0];
            $months[$month] = ['units' => $figures['units'], 'gross' => $figures['gross'], 'revenue' => $figures['revenue'], 'received' => $flow['received'], 'sold' => $flow['sold'], 'lost' => $flow['lost'], 'used' => $flow['used'], 'onHand' => $level];
            $level -= $flow['net'];
        }

        $discounts = $this->discountsOf($product, $range);

        return new ProductReportView(
            [
                'id' => (string) $product->id(),
                'name' => $product->displayName(),
                'typeName' => $product->type()->name(),
                'sellingPrice' => $product->sellingPrice()->amount(),
                'onHand' => $onHand,
            ],
            ['from' => $range->start()->format('Y-m-d'), 'to' => $range->end()->format('Y-m-d'), 'days' => $range->days()],
            self::series($months),
            $discounts,
            self::coveredDays($discounts),
        );
    }

    /**
     * @param array<string, array{units: int, gross: int, revenue: int, received: int, sold: int, lost: int, used: int, onHand: int}> $months
     *
     * @return list<array{month: string, units: int, gross: int, revenue: int, received: int, sold: int, lost: int, used: int, onHand: int}>
     */
    private static function series(array $months): array
    {
        $series = [];
        foreach ($months as $month => $figures) {
            $series[] = ['month' => (string) $month, ...$figures];
        }

        return $series;
    }

    /**
     * @return list<array{id: string, name: string, from: string, to: string, days: int}>
     */
    private function discountsOf(Product $product, DateRange $range): array
    {
        $discounts = [];
        foreach ($this->rules->all() as $rule) {
            $within = $rule->concerns($product) ? $rule->validity()->within($range) : null;
            if (null !== $within) {
                $discounts[] = self::discount($rule, $within);
            }
        }
        usort($discounts, static fn (array $a, array $b): int => $a['from'] <=> $b['from']);

        return $discounts;
    }

    /**
     * @return array{id: string, name: string, from: string, to: string, days: int}
     */
    private static function discount(DiscountRule $rule, DateRange $within): array
    {
        return ['id' => (string) $rule->id(), 'name' => $rule->name(), 'from' => $within->start()->format('Y-m-d'), 'to' => $within->end()->format('Y-m-d'), 'days' => $within->days()];
    }

    /**
     * @param list<array{id: string, name: string, from: string, to: string, days: int}> $discounts sorted by start
     */
    private static function coveredDays(array $discounts): int
    {
        $days = 0;
        $reached = null;
        foreach ($discounts as $discount) {
            $from = null === $reached || $discount['from'] > $reached ? $discount['from'] : (new \DateTimeImmutable($reached))->modify('+1 day')->format('Y-m-d');
            if ($from <= $discount['to']) {
                $days += (new \DateTimeImmutable($from))->diff(new \DateTimeImmutable($discount['to']))->days + 1;
            }
            $reached = null === $reached || $discount['to'] > $reached ? $discount['to'] : $reached;
        }

        return $days;
    }
}
