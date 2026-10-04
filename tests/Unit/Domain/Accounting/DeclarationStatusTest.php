<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Accounting;

use App\Domain\Accounting\DeclarationPeriod;
use App\Domain\Accounting\DeclarationStatus;
use App\Domain\Accounting\UrssafDeclaration;
use App\Domain\Shared\BusinessTime;
use App\Domain\Shared\Money;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;

final class DeclarationStatusTest extends TestCase
{
    private const string ACTIVITY_START = '2026-02-10 10:00';

    public function testAPeriodFollowsTheCalendarUntilDeclared(): void
    {
        $september = DeclarationPeriod::fromKey('2026-09');

        self::assertSame(DeclarationStatus::Upcoming, self::statusOf($september, '2026-08-20 10:00'));
        self::assertSame(DeclarationStatus::Current, self::statusOf($september, '2026-09-20 10:00'));
        self::assertSame(DeclarationStatus::Due, self::statusOf($september, '2026-10-20 10:00'));
        self::assertSame(DeclarationStatus::Late, self::statusOf($september, '2026-11-01 10:00'));
    }

    public function testADeclaredPeriodIsChangedWhenItsTurnoverMoved(): void
    {
        $september = DeclarationPeriod::fromKey('2026-09');
        $declaration = UrssafDeclaration::record(TestWorkspace::get(), $september, Money::cents(10_000), BusinessTime::at('2026-10-05 10:00'));

        self::assertSame(DeclarationStatus::Declared, self::statusOf($september, '2026-11-20 10:00', Money::cents(10_000), $declaration));
        self::assertSame(DeclarationStatus::Changed, self::statusOf($september, '2026-11-20 10:00', Money::cents(12_000), $declaration));
    }

    public function testAPeriodEndingBeforeTheFirstOrderIsInactive(): void
    {
        self::assertSame(DeclarationStatus::Inactive, self::statusOf(DeclarationPeriod::fromKey('2026-01'), '2026-06-20 10:00'));
        self::assertSame(DeclarationStatus::Late, self::statusOf(DeclarationPeriod::fromKey('2026-02'), '2026-06-20 10:00'));
    }

    public function testOnlyPeriodsToDeclareLateOrChangedArePending(): void
    {
        self::assertSame(
            [DeclarationStatus::Due, DeclarationStatus::Late, DeclarationStatus::Changed],
            array_values(array_filter(DeclarationStatus::cases(), static fn (DeclarationStatus $status): bool => $status->isPending())),
        );
    }

    private static function statusOf(DeclarationPeriod $period, string $today, ?Money $turnover = null, ?UrssafDeclaration $declaration = null): DeclarationStatus
    {
        return DeclarationStatus::of($period, $turnover ?? Money::zero(), $declaration, BusinessTime::at($today), BusinessTime::at(self::ACTIVITY_START));
    }
}
