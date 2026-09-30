<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Accounting;

use App\Domain\Accounting\DeclarationPeriod;
use App\Domain\Accounting\DeclarationPeriodicity;
use App\Domain\Accounting\Exception\InvalidDeclaration;
use App\Domain\Accounting\UrssafDeclaration;
use App\Domain\Shared\Money;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;

final class DeclarationPeriodTest extends TestCase
{
    public function testAMonthIsDeclaredByTheEndOfTheFollowingMonth(): void
    {
        $period = DeclarationPeriod::containing(new \DateTimeImmutable('2026-09-15 10:00', new \DateTimeZone('Europe/Paris')), DeclarationPeriodicity::Monthly);

        self::assertSame('2026-09', $period->key());
        self::assertSame(['2026-09-01', '2026-09-30', '2026-10-31'], [$period->start()->format('Y-m-d'), $period->end()->format('Y-m-d'), $period->deadline()->format('Y-m-d')]);
    }

    public function testAQuarterIsDeclaredByTheEndOfTheMonthAfterIt(): void
    {
        $deadlines = array_map(static fn (DeclarationPeriod $period): string => $period->key().' '.$period->deadline()->format('Y-m-d'), DeclarationPeriod::ofYear(2026, DeclarationPeriodicity::Quarterly));

        self::assertSame(['2026-T1 2026-04-30', '2026-T2 2026-07-31', '2026-T3 2026-10-31', '2026-T4 2027-01-31'], $deadlines);
    }

    public function testPeriodsFollowParisTime(): void
    {
        $period = DeclarationPeriod::containing(new \DateTimeImmutable('2026-06-30 23:30:00+00:00'), DeclarationPeriodicity::Quarterly);

        self::assertSame('2026-T3', $period->key());
    }

    public function testKeysRoundTrip(): void
    {
        self::assertSame('2026-T4', DeclarationPeriod::fromKey('2026-T4')->key());
        self::assertSame('2026-02', DeclarationPeriod::fromKey('2026-02')->key());

        $this->expectException(InvalidDeclaration::class);
        DeclarationPeriod::fromKey('2026-13');
    }

    public function testLateOnceTheDeadlineHasPassed(): void
    {
        $period = DeclarationPeriod::fromKey('2026-07');

        self::assertFalse($period->isOverOn(new \DateTimeImmutable('2026-07-31 12:00')));
        self::assertTrue($period->isOverOn(new \DateTimeImmutable('2026-08-01 12:00')));
        self::assertFalse($period->isLateOn(new \DateTimeImmutable('2026-08-31 12:00')));
        self::assertTrue($period->isLateOn(new \DateTimeImmutable('2026-09-01 12:00')));
    }

    public function testAPeriodIsDeclaredOnlyOnceItIsOver(): void
    {
        $this->expectException(InvalidDeclaration::class);

        UrssafDeclaration::record(TestWorkspace::get(), DeclarationPeriod::fromKey('2026-09'), Money::cents(100), new \DateTimeImmutable('2026-09-15'));
    }
}
