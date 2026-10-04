<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

final class SalesYears
{
    /**
     * @param list<string> $months
     *
     * @return list<int>
     */
    public static function of(array $months, int ...$included): array
    {
        $years = array_values(array_unique([...$included, ...array_map(static fn (string $month): int => (int) substr($month, 0, 4), $months)]));
        rsort($years);

        return $years;
    }
}
