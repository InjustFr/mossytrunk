<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Reporting;

use App\Domain\Shared\BusinessTime;
use App\Domain\Shared\DateRange;

final class SalePeriodBounds
{
    /**
     * @return array{\DateTimeImmutable, \DateTimeImmutable}
     */
    public static function of(DateRange $period): array
    {
        return [
            BusinessTime::at($period->start()->format('Y-m-d')),
            BusinessTime::at($period->end()->format('Y-m-d').' +1 day'),
        ];
    }
}
