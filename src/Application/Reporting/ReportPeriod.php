<?php

declare(strict_types=1);

namespace App\Application\Reporting;

use App\Domain\Order\OrderRepository;
use App\Domain\Shared\DateRange;
use Psr\Clock\ClockInterface;

final readonly class ReportPeriod
{
    public const string LAST_TWELVE_MONTHS = '12m';
    public const string SINCE_THE_START = 'all';

    public function __construct(
        private OrderRepository $orders,
        private ClockInterface $clock,
    ) {
    }

    public function resolve(string $choice): DateRange
    {
        $today = $this->clock->now()->setTimezone(new \DateTimeZone(DateRange::TIMEZONE));

        return match (true) {
            1 === preg_match('/^\d{4}$/', $choice) => DateRange::year((int) $choice),
            self::SINCE_THE_START === $choice => DateRange::fromDates(min($this->orders->firstSaleAt() ?? $today, $today), max($this->orders->lastSaleAt() ?? $today, $today)),
            default => DateRange::fromDates($today->modify('first day of this month')->modify('-11 months'), $today),
        };
    }

    /**
     * @return list<int> the years with sales, most recent first
     */
    public function years(): array
    {
        $today = $this->clock->now();
        $first = $this->orders->firstSaleAt() ?? $today;
        $last = $this->orders->lastSaleAt() ?? $today;

        return range(DateRange::yearOf(max($today, $last)), DateRange::yearOf(min($today, $first)));
    }

    /**
     * @return list<string> the months of the period, as YYYY-MM, oldest first
     */
    public static function months(DateRange $period): array
    {
        $months = [];
        $month = $period->start()->modify('first day of this month');
        while ($month <= $period->end()) {
            $months[] = $month->format('Y-m');
            $month = $month->modify('+1 month');
        }

        return $months;
    }

    public static function monthOf(\DateTimeImmutable $moment): string
    {
        return $moment->setTimezone(new \DateTimeZone(DateRange::TIMEZONE))->format('Y-m');
    }
}
