<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Order\Order;
use App\Domain\Reporting\SalesTotals;
use App\Domain\Shared\Money;

final class RecordedSales
{
    /**
     * @param list<Order> $orders
     */
    public static function totals(array $orders): SalesTotals
    {
        return new SalesTotals(
            \count($orders),
            Money::sum(array_map(static fn (Order $order): Money => $order->subtotal(), $orders)),
            Money::sum(array_map(static fn (Order $order): Money => $order->discountTotal(), $orders)),
            Money::sum(array_map(static fn (Order $order): Money => $order->shipping(), $orders)),
            Money::sum(array_map(static fn (Order $order): Money => $order->costOfGoods(), $orders)),
            Money::sum(array_map(static fn (Order $order): Money => $order->suppliesCost(), $orders)),
            Money::sum(array_map(static fn (Order $order): Money => $order->channelCosts(), $orders)),
        );
    }
}
