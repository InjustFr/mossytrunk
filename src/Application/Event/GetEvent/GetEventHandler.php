<?php

declare(strict_types=1);

namespace App\Application\Event\GetEvent;

use App\Domain\Event\EventRepository;
use Symfony\Component\Uid\Ulid;

final readonly class GetEventHandler
{
    public function __construct(private EventRepository $events)
    {
    }

    public function __invoke(string $eventId): EventView
    {
        return EventView::fromEvent($this->events->get(Ulid::fromString($eventId)));
    }
}
