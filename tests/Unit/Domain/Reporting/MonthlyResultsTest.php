<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Reporting;

use App\Domain\Event\Event;
use App\Domain\Event\ExpenseShares;
use App\Domain\Reporting\MonthlyResults;
use App\Domain\Reporting\SalesTotals;
use App\Domain\Shared\DateRange;
use App\Domain\Shared\Money;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;

final class MonthlyResultsTest extends TestCase
{
    public function testSalesCountInTheirMonthAndExpensesInTheEventStartMonth(): void
    {
        $event = Event::schedule(TestWorkspace::get(), 'Salon', 'Lyon', DateRange::fromDates(new \DateTimeImmutable('2026-04-30'), new \DateTimeImmutable('2026-05-01')));
        $event->addExpense('Stand', Money::cents(5_000));
        $december = Event::schedule(TestWorkspace::get(), 'Marché', 'Lyon', DateRange::fromDates(new \DateTimeImmutable('2025-12-31'), new \DateTimeImmutable('2025-12-31')));

        $results = MonthlyResults::of([
            '2026-04' => self::sales(1, 2_000, 400),
            '2026-05' => self::sales(1, 1_000, 200),
            '2025-12' => self::sales(1, 1_000, 200),
        ], [$event, $december], ExpenseShares::among([$event, $december]));

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

    private static function sales(int $orders, int $gross, int $cost): SalesTotals
    {
        return new SalesTotals($orders, Money::cents($gross), Money::zero(), Money::zero(), Money::cents($cost), Money::zero(), Money::zero());
    }
}
