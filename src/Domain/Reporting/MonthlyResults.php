<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Domain\Event\Event;
use App\Domain\Order\Order;
use App\Domain\Shared\DateRange;
use App\Domain\Shared\Money;

/**
 * Results per calendar month (Europe/Paris):
 * - an order counts in the month of its date;
 * - an event's expenses count in the month the event starts.
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
     * @param list<Order> $orders
     * @param list<Event> $events
     */
    public static function of(array $orders, array $events): self
    {
        $ordersByMonth = [];
        foreach ($orders as $order) {
            $ordersByMonth[self::monthKey($order->placedAt())][] = $order;
        }

        $expensesByMonth = [];
        foreach ($events as $event) {
            $month = self::monthKey($event->period()->start());
            $expensesByMonth[$month] = ($expensesByMonth[$month] ?? Money::zero())->add($event->totalExpenses());
        }

        $months = [];
        foreach (array_unique([...array_keys($ordersByMonth), ...array_keys($expensesByMonth)]) as $month) {
            $months[$month] = SalesFigures::of($ordersByMonth[$month] ?? [], $expensesByMonth[$month] ?? Money::zero());
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
