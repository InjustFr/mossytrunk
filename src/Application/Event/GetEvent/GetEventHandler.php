<?php

declare(strict_types=1);

namespace App\Application\Event\GetEvent;

use App\Domain\Event\EventRepository;
use App\Domain\Event\ExpenseShares;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Ulid;

final readonly class GetEventHandler
{
    public function __construct(
        private EventRepository $events,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(string $eventId): EventView
    {
        $event = $this->events->get(Ulid::fromString($eventId));

        return EventView::fromEvent($event, $event->timingOn($this->clock->now()), ExpenseShares::among($this->events->all()));
    }
}
