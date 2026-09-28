<?php

declare(strict_types=1);

namespace App\Application\Event\ListEvents;

use App\Domain\Event\EventRepository;
use App\Domain\Order\Order;
use App\Domain\Order\OrderRepository;
use App\Domain\Reporting\EventResult;

/**
 * Events with their headline figures (turnover and result, see docs/business/event-report.md).
 */
final readonly class ListEventsHandler
{
    public function __construct(
        private EventRepository $events,
        private OrderRepository $orders,
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
            $ordersByEvent[(string) $order->event()->id()][] = $order;
        }

        $views = [];
        foreach ($this->events->all() as $event) {
            $views[] = EventSummaryView::of($event, EventResult::of($event, $ordersByEvent[(string) $event->id()] ?? []));
        }

        return $views;
    }
}
