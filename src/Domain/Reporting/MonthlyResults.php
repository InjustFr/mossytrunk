<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Domain\Event\Event;
use App\Domain\Shared\DateRange;
use App\Domain\Shared\Money;

/**
 * Results per calendar month (Europe/Paris):
 * - an order counts in the month of its date;
 * - an event's expenses and the supplies consumed at it count in the month the event starts.
 */
final readonly class MonthlyResults
{
    /**
     * @param array<string, SalesFigures> $months keyed "YYYY-MM"
     */
    private function __construct(private array $months)
    {
    }

    /**
     * @param array<string, SalesTotals> $salesByMonth            keyed "YYYY-MM" (Europe/Paris)
     * @param list<Event>                $events
     * @param array<string, Money>       $consumedSuppliesByEvent keyed by event id
     */
    public static function of(array $salesByMonth, array $events, array $consumedSuppliesByEvent = []): self
    {
        $expensesByMonth = [];
        $consumedByMonth = [];
        foreach ($events as $event) {
            $month = self::monthKey($event->period()->start());
            $expensesByMonth[$month] = ($expensesByMonth[$month] ?? Money::zero())->add($event->totalExpenses());
            $consumedByMonth[$month] = ($consumedByMonth[$month] ?? Money::zero())->add($consumedSuppliesByEvent[(string) $event->id()] ?? Money::zero());
        }

        $months = [];
        foreach (array_unique([...array_keys($salesByMonth), ...array_keys($expensesByMonth)]) as $month) {
            $months[$month] = SalesFigures::of($salesByMonth[$month] ?? SalesTotals::zero(), $expensesByMonth[$month] ?? Money::zero(), $consumedByMonth[$month] ?? Money::zero());
        }

        return new self($months);
    }

    public function month(int $year, int $month): SalesFigures
    {
        return $this->months[\sprintf('%04d-%02d', $year, $month)] ?? SalesFigures::zero();
    }

    public function year(int $year): SalesFigures
    {
        $total = SalesFigures::zero();
        for ($month = 1; $month <= 12; ++$month) {
            $total = $total->add($this->month($year, $month));
        }

        return $total;
    }

    /**
     * @return list<int> years having orders or expenses, most recent first
     */
    public function years(): array
    {
        $years = array_values(array_unique(array_map(static fn (string $month): int => (int) substr($month, 0, 4), array_keys($this->months))));
        rsort($years);

        return $years;
    }

    private static function monthKey(\DateTimeImmutable $moment): string
    {
        return $moment->setTimezone(new \DateTimeZone(DateRange::TIMEZONE))->format('Y-m');
    }
}
