<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Domain\Order\Order;
use App\Domain\Shared\Money;

/**
 * Profitability figures of a set of orders and expenses (an event, a month, a year):
 *   turnover = gross sales − discounts + shipping charged
 *   URSSAF   = 12.8 % × turnover
 *   result   = turnover − cost of goods − supplies − channel costs − expenses − URSSAF
 */
final readonly class SalesFigures
{
    private function __construct(
        public int $orderCount,
        public Money $grossSales,
        public Money $discounts,
        public Money $shipping,
        public Money $turnover,
        public Money $costOfGoods,
        public Money $supplies,
        public Money $channelCosts,
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
        $shipping = Money::sum(array_map(static fn (Order $order): Money => $order->shipping(), $orders));
        $turnover = $grossSales->subtract($discounts)->add($shipping);
        $costOfGoods = Money::sum(array_map(static fn (Order $order): Money => $order->costOfGoods(), $orders));
        $supplies = Money::sum(array_map(static fn (Order $order): Money => $order->suppliesCost(), $orders));
        $channelCosts = Money::sum(array_map(static fn (Order $order): Money => $order->channelCosts(), $orders));
        $urssaf = UrssafContribution::on($turnover);

        return new self(
            \count($orders),
            $grossSales,
            $discounts,
            $shipping,
            $turnover,
            $costOfGoods,
            $supplies,
            $channelCosts,
            $expenses,
            $urssaf,
            $turnover->subtract($costOfGoods)->subtract($supplies)->subtract($channelCosts)->subtract($expenses)->subtract($urssaf),
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
            $this->shipping->add($other->shipping),
            $this->turnover->add($other->turnover),
            $this->costOfGoods->add($other->costOfGoods),
            $this->supplies->add($other->supplies),
            $this->channelCosts->add($other->channelCosts),
            $this->expenses->add($other->expenses),
            $this->urssaf->add($other->urssaf),
            $this->result->add($other->result),
        );
    }

    /**
     * @return array{orderCount: int, grossSales: int, discounts: int, shipping: int, turnover: int, costOfGoods: int, supplies: int, channelCosts: int, expenses: int, urssaf: int, result: int}
     */
    public function toArray(): array
    {
        return [
            'orderCount' => $this->orderCount,
            'grossSales' => $this->grossSales->amount(),
            'discounts' => $this->discounts->amount(),
            'shipping' => $this->shipping->amount(),
            'turnover' => $this->turnover->amount(),
            'costOfGoods' => $this->costOfGoods->amount(),
            'supplies' => $this->supplies->amount(),
            'channelCosts' => $this->channelCosts->amount(),
            'expenses' => $this->expenses->amount(),
            'urssaf' => $this->urssaf->amount(),
            'result' => $this->result->amount(),
        ];
    }
}
