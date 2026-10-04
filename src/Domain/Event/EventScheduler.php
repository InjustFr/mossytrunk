<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Event\Exception\OverlappingEvent;
use App\Domain\Shared\DateRange;
use Symfony\Component\Uid\Ulid;

final readonly class EventScheduler
{
    public function __construct(private EventRepository $events)
    {
    }

    public function ensureFree(DateRange $period, ?Ulid $except = null): void
    {
        $overlapping = $this->events->findOverlapping($period, $except);

        if (null !== $overlapping) {
            throw new OverlappingEvent($overlapping->name());
        }
    }
}
