<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Reporting;

use App\Domain\Event\Event;
use App\Domain\Order\Order;
use App\Domain\Order\OrderedItem;
use App\Domain\Product\Product;
use App\Domain\Reporting\MonthlyResults;
use App\Domain\Shared\DateRange;
use App\Domain\Shared\Money;
use App\Tests\Support\Costs;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;

final class MonthlyResultsTest extends TestCase
{
    public function testOrdersCountInTheirParisMonthAndExpensesInTheEventStartMonth(): void
    {
        $print = Costs::bought(Product::create(TestWorkspace::get(), 'PRI', 'Print', Money::cents(1_000)), 200);
        // Event across a month boundary: 30 April → 1 May 2026.
        $event = Event::schedule(TestWorkspace::get(), 'Salon', 'Lyon', DateRange::fromDates(new \DateTimeImmutable('2026-04-30'), new \DateTimeImmutable('2026-05-01')));
        $event->addExpense('Stand', Money::cents(5_000));
        $december = Event::schedule(TestWorkspace::get(), 'Marché', 'Lyon', DateRange::fromDates(new \DateTimeImmutable('2025-12-31'), new \DateTimeImmutable('2025-12-31')));

        $orders = [
            Order::place($event, new \DateTimeImmutable('2026-04-30 18:00', new \DateTimeZone('Europe/Paris')), [new OrderedItem($print->sellable(null), 2)], []),
            // 30 April 22:30 UTC is already 1 May in Paris.
            Order::place($event, new \DateTimeImmutable('2026-04-30T22:30:00+00:00'), [new OrderedItem($print->sellable(null), 1)], []),
            Order::place($december, new \DateTimeImmutable('2025-12-31 15:00', new \DateTimeZone('Europe/Paris')), [new OrderedItem($print->sellable(null), 1)], []),
        ];

        $results = MonthlyResults::of($orders, [$event, $december]);

        $april = $results->month(2026, 4);
        self::assertSame(1, $april->orderCount);
        self::assertSame(2_000, $april->turnover->amount());
        self::assertSame(5_000, $april->expenses->amount());
        self::assertSame(256, $april->urssaf->amount());
        self::assertSame(2_000 - 400 - 5_000 - 256, $april->result->amount());

        $may = $results->month(2026, 5);
        self::assertSame(1_000, $may->turnover->amount());
        self::assertTrue($may->expenses->isZero());

        self::assertTrue($results->month(2026, 6)->turnover->isZero());
        self::assertSame(3_000, $results->year(2026)->turnover->amount());
        self::assertSame(2, $results->year(2026)->orderCount);
        self::assertSame(1_000, $results->year(2025)->turnover->amount());
        self::assertSame([2026, 2025], $results->years());
    }
}
