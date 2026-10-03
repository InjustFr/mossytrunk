<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Reporting;

use App\Domain\Shared\DateRange;

final class SalePeriodBounds
{
    /**
     * @return array{\DateTimeImmutable, \DateTimeImmutable} first moment of the period, first moment after it (Europe/Paris days)
     */
    public static function of(DateRange $period): array
    {
        $timezone = new \DateTimeZone(DateRange::TIMEZONE);

        return [
            new \DateTimeImmutable($period->start()->format('Y-m-d'), $timezone),
            new \DateTimeImmutable($period->end()->format('Y-m-d').' +1 day', $timezone),
        ];
    }
}
