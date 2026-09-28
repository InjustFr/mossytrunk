<?php

declare(strict_types=1);

namespace App\Application\Event\GetEventReport;

use App\Domain\Event\EventRepository;
use App\Domain\Order\OrderRepository;
use App\Domain\Reporting\EventResult;
use Symfony\Component\Uid\Ulid;

final readonly class GetEventReportHandler
{
    public function __construct(
        private EventRepository $events,
        private OrderRepository $orders,
    ) {
    }

    public function __invoke(string $eventId): EventReportView
    {
        $event = $this->events->get(Ulid::fromString($eventId));

        return EventReportView::of($event, EventResult::of($event, $this->orders->list($event->id())));
    }
}
