<?php

declare(strict_types=1);

namespace App\Application\Accounting;

use App\Domain\Accounting\DeclarationPeriod;
use App\Domain\Reporting\SalesTotals;
use App\Domain\Shared\Money;

final readonly class PeriodTurnover
{
    public function __construct(
        public DeclarationPeriod $period,
        public Money $turnover,
        public int $orderCount,
    ) {
    }

    /**
     * @param array<string, SalesTotals> $salesByMonth
     */
    public static function of(DeclarationPeriod $period, array $salesByMonth): self
    {
        $sales = SalesTotals::zero();
        foreach ($period->months() as $month) {
            $sales = $sales->add($salesByMonth[$month] ?? SalesTotals::zero());
        }

        return new self($period, $sales->turnover(), $sales->orderCount);
    }
}
