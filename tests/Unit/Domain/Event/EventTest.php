<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Event;

use App\Domain\Event\Event;
use App\Domain\Event\InvalidEvent;
use App\Domain\Shared\DateRange;
use App\Domain\Shared\InvalidMoney;
use App\Domain\Shared\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

final class EventTest extends TestCase
{
    public function testNameAndLocationAreRequired(): void
    {
        $this->expectException(InvalidEvent::class);

        Event::schedule('Japan Expo', '  ', self::period());
    }

    public function testCoversDaysOfItsPeriod(): void
    {
        $event = Event::schedule('Japan Expo', 'Villepinte', self::period());

        self::assertTrue($event->covers(new \DateTimeImmutable('2026-07-10 18:00', new \DateTimeZone('Europe/Paris'))));
        self::assertFalse($event->covers(new \DateTimeImmutable('2026-07-13 09:00', new \DateTimeZone('Europe/Paris'))));
    }

    public function testExpensesAreSummedAndRemovable(): void
    {
        $event = Event::schedule('Japan Expo', 'Villepinte', self::period());
        $stand = $event->addExpense('Stand', Money::cents(30_000));
        $event->addExpense('Train', Money::cents(8_950));

        self::assertSame(38_950, $event->totalExpenses()->amount());

        $event->removeExpense($stand->id());
        self::assertSame(8_950, $event->totalExpenses()->amount());
        self::assertCount(1, $event->expenses());
    }

    public function testExpenseCanBeRevisedWithTheSameRules(): void
    {
        $event = Event::schedule('Japan Expo', 'Villepinte', self::period());
        $stand = $event->addExpense('Stand', Money::cents(30_000));

        $event->reviseExpense($stand->id(), 'Stand (angle)', Money::cents(32_000));
        self::assertSame('Stand (angle)', $event->expenses()[0]->label());
        self::assertSame(32_000, $event->totalExpenses()->amount());

        $this->expectException(InvalidMoney::class);
        $event->reviseExpense($stand->id(), 'Stand', Money::zero());
    }

    public function testRemovingUnknownExpenseFails(): void
    {
        $event = Event::schedule('Japan Expo', 'Villepinte', self::period());

        $this->expectException(InvalidEvent::class);
        $event->removeExpense(new Ulid());
    }

    public function testExpenseAmountMustBePositive(): void
    {
        $event = Event::schedule('Japan Expo', 'Villepinte', self::period());

        $this->expectException(InvalidMoney::class);
        $event->addExpense('Stand', Money::zero());
    }

    public function testExpenseLabelIsRequired(): void
    {
        $event = Event::schedule('Japan Expo', 'Villepinte', self::period());

        $this->expectException(InvalidEvent::class);
        $event->addExpense(' ', Money::cents(100));
    }

    private static function period(): DateRange
    {
        return DateRange::fromDates(new \DateTimeImmutable('2026-07-09'), new \DateTimeImmutable('2026-07-12'));
    }
}
