<?php

declare(strict_types=1);

namespace App\Application\Event\ListEvents;

use App\Domain\Event\EventRepository;
use App\Domain\Order\Order;
use App\Domain\Order\OrderRepository;
use App\Domain\Reporting\EventResult;
use App\Domain\Stock\StockCheckRepository;
use Psr\Clock\ClockInterface;

/**
 * Events with their headline figures (turnover and result, see docs/business/event-report.md)
 * and their timing relative to today (upcoming, ongoing, past).
 */
final readonly class ListEventsHandler
{
    public function __construct(
        private EventRepository $events,
        private OrderRepository $orders,
        private StockCheckRepository $checks,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return list<EventSummaryView>
     */
    public function __invoke(): array
    {
        /** @var array<string, list<Order>> $ordersByEvent */
        $ordersByEvent = [];
        foreach ($this->orders->list() as $order) {
            $event = $order->event();
            if (null !== $event) {
                $ordersByEvent[(string) $event->id()][] = $order;
            }
        }

        $unexplained = [];
        foreach ($this->checks->withUnexplainedUnits() as $check) {
            $key = (string) $check->event()->id();
            $unexplained[$key] = ($unexplained[$key] ?? 0) + $check->unexplainedUnits();
        }

        $today = $this->clock->now();
        $views = [];
        foreach ($this->events->all() as $event) {
            $views[] = EventSummaryView::of($event, EventResult::of($event, $ordersByEvent[(string) $event->id()] ?? []), $event->timingOn($today), $unexplained[(string) $event->id()] ?? 0);
        }

        return $views;
    }
}
