<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Shared;

use App\Domain\Shared\DateRange;
use App\Domain\Shared\InvalidDateRange;
use PHPUnit\Framework\TestCase;

final class DateRangeTest extends TestCase
{
    public function testEndCannotPrecedeStart(): void
    {
        $this->expectException(InvalidDateRange::class);

        DateRange::fromDates(new \DateTimeImmutable('2026-05-10'), new \DateTimeImmutable('2026-05-09'));
    }

    public function testSingleDayRangeIsAllowed(): void
    {
        $range = DateRange::fromDates(new \DateTimeImmutable('2026-05-10'), new \DateTimeImmutable('2026-05-10'));

        self::assertTrue($range->covers(new \DateTimeImmutable('2026-05-10 23:59:59', new \DateTimeZone('Europe/Paris'))));
    }

    public function testCoversWholeDaysInclusively(): void
    {
        $range = DateRange::fromDates(new \DateTimeImmutable('2026-05-09'), new \DateTimeImmutable('2026-05-10'));
        $paris = new \DateTimeZone('Europe/Paris');

        self::assertTrue($range->covers(new \DateTimeImmutable('2026-05-09 00:00:00', $paris)));
        self::assertTrue($range->covers(new \DateTimeImmutable('2026-05-10 23:59:59', $paris)));
        self::assertFalse($range->covers(new \DateTimeImmutable('2026-05-08 23:59:59', $paris)));
        self::assertFalse($range->covers(new \DateTimeImmutable('2026-05-11 00:00:00', $paris)));
    }

    public function testCoverageUsesParisLocalDate(): void
    {
        $range = DateRange::fromDates(new \DateTimeImmutable('2026-05-10'), new \DateTimeImmutable('2026-05-10'));

        // 22:30 UTC on the 10th is already 00:30 on the 11th in Paris (UTC+2 in May).
        self::assertFalse($range->covers(new \DateTimeImmutable('2026-05-10T22:30:00+00:00')));
        // 22:30 UTC on the 9th is 00:30 on the 10th in Paris.
        self::assertTrue($range->covers(new \DateTimeImmutable('2026-05-09T22:30:00+00:00')));
    }

    public function testOverlaps(): void
    {
        $may = DateRange::fromDates(new \DateTimeImmutable('2026-05-09'), new \DateTimeImmutable('2026-05-10'));

        self::assertTrue($may->overlaps(DateRange::fromDates(new \DateTimeImmutable('2026-05-10'), new \DateTimeImmutable('2026-05-12'))));
        self::assertFalse($may->overlaps(DateRange::fromDates(new \DateTimeImmutable('2026-05-11'), new \DateTimeImmutable('2026-05-12'))));
    }

    public function testCountsDaysInclusively(): void
    {
        self::assertSame(1, DateRange::fromDates(new \DateTimeImmutable('2026-05-10'), new \DateTimeImmutable('2026-05-10'))->days());
        self::assertSame(4, DateRange::fromDates(new \DateTimeImmutable('2026-07-09'), new \DateTimeImmutable('2026-07-12'))->days());
    }

    public function testYearOfUsesParisLocalDate(): void
    {
        self::assertSame(2027, DateRange::yearOf(new \DateTimeImmutable('2026-12-31T23:30:00+00:00')));
        self::assertSame(2026, DateRange::yearOf(new \DateTimeImmutable('2026-12-31T22:30:00+00:00')));
    }
}
