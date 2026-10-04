<?php

declare(strict_types=1);

namespace App\Domain\Accounting;

use App\Domain\Shared\BusinessTime;
use App\Domain\Shared\Money;

enum DeclarationStatus: string
{
    case Upcoming = 'upcoming';
    case Current = 'current';
    case Due = 'due';
    case Late = 'late';
    case Declared = 'declared';
    case Changed = 'changed';
    case Inactive = 'inactive';

    public static function of(DeclarationPeriod $period, Money $turnover, ?UrssafDeclaration $declaration, \DateTimeImmutable $today, \DateTimeImmutable $activityStart): self
    {
        return match (true) {
            null !== $declaration => $declaration->turnover()->equals($turnover) ? self::Declared : self::Changed,
            $period->end()->format('Y-m-d') < BusinessTime::day($activityStart) => self::Inactive,
            !$period->isOverOn($today) => $period->covers($today) ? self::Current : self::Upcoming,
            $period->isLateOn($today) => self::Late,
            default => self::Due,
        };
    }

    public function isPending(): bool
    {
        return \in_array($this, [self::Due, self::Late, self::Changed], true);
    }
}
