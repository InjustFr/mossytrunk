<?php

declare(strict_types=1);

namespace App\Application\Accounting\GetUrssafOverview;

use App\Application\Accounting\PeriodTurnover;
use App\Application\Reporting\SalesLedger;
use App\Application\WorkspaceContext;
use App\Domain\Accounting\DeclarationPeriod;
use App\Domain\Accounting\UrssafDeclarationRepository;
use App\Domain\Order\OrderRepository;
use App\Domain\Reporting\UrssafContribution;
use App\Domain\Shared\DateRange;
use Psr\Clock\ClockInterface;

final readonly class GetUrssafOverviewHandler
{
    public function __construct(
        private OrderRepository $orders,
        private SalesLedger $sales,
        private UrssafDeclarationRepository $declarations,
        private WorkspaceContext $workspace,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(?int $year = null): UrssafOverview
    {
        $today = $this->clock->now();
        $periodicity = $this->workspace->current()->declarationPeriodicity();
        $year ??= DateRange::yearOf($today);
        $salesByMonth = $this->sales->totalsByMonth();
        $declarations = [];
        foreach ($this->declarations->all() as $declaration) {
            $declarations[$declaration->period()] = $declaration;
        }
        $first = $this->orders->firstSaleAt() ?? $today;
        $view = fn (DeclarationPeriod $period): DeclarationPeriodView => DeclarationPeriodView::of(PeriodTurnover::of($period, $salesByMonth), $declarations[$period->key()] ?? null, $today, $first);

        $periods = array_map($view, DeclarationPeriod::ofYear($year, $periodicity));
        $years = array_values(array_unique([DateRange::yearOf($today), $year, ...array_map(static fn (string $month): int => (int) substr($month, 0, 4), array_keys($salesByMonth))]));
        rsort($years);

        $pending = [];
        for ($period = DeclarationPeriod::containing($first, $periodicity); $period->isOverOn($today); $period = DeclarationPeriod::containing($period->end()->modify('+1 day'), $periodicity)) {
            $candidate = $view($period);
            if (\in_array($candidate->status, ['due', 'late', 'changed'], true)) {
                $pending[] = $candidate;
            }
        }

        return new UrssafOverview(
            $year,
            $years,
            $periodicity->value,
            UrssafContribution::RATE_BASIS_POINTS / 100,
            $periods,
            $pending,
            array_sum(array_map(static fn (DeclarationPeriodView $period): int => $period->turnover, $periods)),
            array_sum(array_map(static fn (DeclarationPeriodView $period): int => $period->contribution, $periods)),
        );
    }
}
