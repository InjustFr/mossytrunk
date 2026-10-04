<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Domain\Shared\Money;

final class UrssafContribution
{
    public const int RATE_BASIS_POINTS = 1_280;

    public static function on(Money $turnover): Money
    {
        return $turnover->percentage(self::RATE_BASIS_POINTS);
    }
}
