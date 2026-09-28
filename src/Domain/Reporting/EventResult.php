<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Domain\Event\Event;
use App\Domain\Order\Order;
use App\Domain\Shared\Money;

/**
 * Profitability of an event:
 *   turnover      = Σ order totals (after discounts)
 *   URSSAF        = 12.8 % × turnover
 *   result        = turnover − cost of goods sold − expenses − URSSAF
 *
 * See docs/business/event-report.md.
 */
final readonly class EventResult
{
    /**
     * @param list<ProductSales> $productSales sorted by sales, best first
     */
    private function __construct(
        public int $orderCount,
        public Money $grossSales,
        public Money $discounts,
        public Money $turnover,
        public Money $costOfGoods,
        public Money $expenses,
        public Money $urssaf,
        public Money $result,
        public array $productSales,
    ) {
    }

    /**
     * @param list<Order> $orders the event's orders
     */
    public static function of(Event $event, array $orders): self
    {
        $grossSales = Money::sum(array_map(static fn (Order $order): Money => $order->subtotal(), $orders));
        $discounts = Money::sum(array_map(static fn (Order $order): Money => $order->discountTotal(), $orders));
        $turnover = $grossSales->subtract($discounts);
        $costOfGoods = Money::sum(array_map(static fn (Order $order): Money => $order->costOfGoods(), $orders));
        $expenses = $event->totalExpenses();
        $urssaf = UrssafContribution::on($turnover);

        return new self(
            \count($orders),
            $grossSales,
            $discounts,
            $turnover,
            $costOfGoods,
            $expenses,
            $urssaf,
            $turnover->subtract($costOfGoods)->subtract($expenses)->subtract($urssaf),
            self::productSales($orders),
        );
    }

    /**
     * @param list<Order> $orders
     *
     * @return list<ProductSales>
     */
    private static function productSales(array $orders): array
    {
        /** @var array<string, ProductSales> $sales */
        $sales = [];
        foreach ($orders as $order) {
            foreach ($order->lines() as $line) {
                $key = $line->productId().'|'.$line->variant();
                $sales[$key] = ($sales[$key] ?? new ProductSales($line->label(), $line->productId(), $line->productName(), $line->variant(), 0, Money::zero(), Money::zero(), false))
                    ->add($line->quantity(), $line->total(), $line->cost(), $line->unitCost()->isZero());
            }
        }

        $sales = array_values($sales);
        usort($sales, static fn (ProductSales $a, ProductSales $b): int => [$b->sales->amount(), $a->label] <=> [$a->sales->amount(), $b->label]);

        return $sales;
    }
}
