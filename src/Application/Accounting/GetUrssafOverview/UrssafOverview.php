<?php

declare(strict_types=1);

namespace App\Application\Accounting\GetUrssafOverview;

final readonly class UrssafOverview
{
    /**
     * @param list<int>                   $years
     * @param list<DeclarationPeriodView> $periods
     * @param list<DeclarationPeriodView> $pending
     */
    public function __construct(
        public int $year,
        public array $years,
        public string $periodicity,
        public float $rate,
        public array $periods,
        public array $pending,
        public int $yearTurnover,
        public int $yearContribution,
    ) {
    }
}
