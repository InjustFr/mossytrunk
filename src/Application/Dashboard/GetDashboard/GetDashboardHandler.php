<?php

declare(strict_types=1);

namespace App\Application\Dashboard\GetDashboard;

use App\Domain\Event\EventRepository;
use App\Domain\Order\OrderRepository;
use App\Domain\Reporting\MonthlyResults;
use App\Domain\Shared\DateRange;

/**
 * Results per month of a year, and per year. See docs/business/dashboard.md.
 */
final readonly class GetDashboardHandler
{
    public function __construct(
        private OrderRepository $orders,
        private EventRepository $events,
    ) {
    }

    /**
     * @param int|null $year defaults to the current year
     */
    public function __invoke(?int $year = null): DashboardView
    {
        $results = MonthlyResults::of($this->orders->list(), $this->events->all());
        $year ??= (int) (new \DateTimeImmutable('now', new \DateTimeZone(DateRange::TIMEZONE)))->format('Y');

        $years = $results->years();
        if (!\in_array($year, $years, true)) {
            $years[] = $year;
            rsort($years);
        }

        $months = [];
        for ($month = 1; $month <= 12; ++$month) {
            $months[] = ['month' => $month] + $results->month($year, $month)->toArray();
        }

        return new DashboardView(
            $year,
            $years,
            $months,
            $results->year($year)->toArray(),
            array_map(static fn (int $y): array => ['year' => $y] + $results->year($y)->toArray(), $results->years()),
        );
    }
}
