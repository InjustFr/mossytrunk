<?php

declare(strict_types=1);

namespace App\Application\Accounting;

use App\Domain\Accounting\DeclarationPeriod;
use App\Domain\Order\Order;
use App\Domain\Shared\Money;

final readonly class PeriodTurnover
{
    public function __construct(
        public DeclarationPeriod $period,
        public Money $turnover,
        public int $orderCount,
    ) {
    }

    /**
     * @param list<Order> $orders
     */
    public static function of(DeclarationPeriod $period, array $orders): self
    {
        $inPeriod = array_values(array_filter($orders, static fn (Order $order): bool => $period->covers($order->placedAt())));

        return new self($period, Money::sum(array_map(static fn (Order $order): Money => $order->total(), $inPeriod)), \count($inPeriod));
    }
}
