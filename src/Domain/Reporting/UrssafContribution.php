<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Domain\Shared\Money;

/**
 * Social contributions owed to l'URSSAF by a micro-entrepreneur selling goods:
 * a flat rate applied to the turnover (what customers actually paid, after discounts).
 */
final class UrssafContribution
{
    /** 12.8 % in basis points. */
    public const int RATE_BASIS_POINTS = 1_280;

    public static function on(Money $turnover): Money
    {
        return $turnover->percentage(self::RATE_BASIS_POINTS);
    }
}
