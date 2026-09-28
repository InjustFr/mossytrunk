<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Domain\Order\Order;
use App\Domain\Shared\Money;

/**
 * Profitability figures of a set of orders and expenses (an event, a month, a year):
 *   turnover = gross sales − discounts
 *   URSSAF   = 12.8 % × turnover
 *   result   = turnover − cost of goods − expenses − URSSAF
 */
final readonly class SalesFigures
{
    private function __construct(
        public int $orderCount,
        public Money $grossSales,
        public Money $discounts,
        public Money $turnover,
        public Money $costOfGoods,
        public Money $expenses,
        public Money $urssaf,
        public Money $result,
    ) {
    }

    /**
     * @param list<Order> $orders
     */
    public static function of(array $orders, Money $expenses): self
    {
        $grossSales = Money::sum(array_map(static fn (Order $order): Money => $order->subtotal(), $orders));
        $discounts = Money::sum(array_map(static fn (Order $order): Money => $order->discountTotal(), $orders));
        $turnover = $grossSales->subtract($discounts);
        $costOfGoods = Money::sum(array_map(static fn (Order $order): Money => $order->costOfGoods(), $orders));
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
        );
    }

    public static function zero(): self
    {
        return self::of([], Money::zero());
    }

    /**
     * Sum of two periods. URSSAF is summed (each period rounded to the cent), so a year equals the sum of its months.
     */
    public function add(self $other): self
    {
        return new self(
            $this->orderCount + $other->orderCount,
            $this->grossSales->add($other->grossSales),
            $this->discounts->add($other->discounts),
            $this->turnover->add($other->turnover),
            $this->costOfGoods->add($other->costOfGoods),
            $this->expenses->add($other->expenses),
            $this->urssaf->add($other->urssaf),
            $this->result->add($other->result),
        );
    }

    /**
     * @return array{orderCount: int, grossSales: int, discounts: int, turnover: int, costOfGoods: int, expenses: int, urssaf: int, result: int}
     */
    public function toArray(): array
    {
        return [
            'orderCount' => $this->orderCount,
            'grossSales' => $this->grossSales->amount(),
            'discounts' => $this->discounts->amount(),
            'turnover' => $this->turnover->amount(),
            'costOfGoods' => $this->costOfGoods->amount(),
            'expenses' => $this->expenses->amount(),
            'urssaf' => $this->urssaf->amount(),
            'result' => $this->result->amount(),
        ];
    }
}
