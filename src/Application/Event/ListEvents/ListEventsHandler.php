<?php

declare(strict_types=1);

namespace App\Application\Event\ListEvents;

use App\Domain\Event\EventRepository;

final readonly class ListEventsHandler
{
    public function __construct(private EventRepository $events)
    {
    }

    /**
     * @return list<EventSummaryView>
     */
    public function __invoke(): array
    {
        return array_map(EventSummaryView::fromEvent(...), $this->events->all());
    }
}
