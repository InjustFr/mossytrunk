<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Shared\DateRange;
use Symfony\Component\Uid\Ulid;

/**
 * Domain service guarding the "events never overlap" rule, which spans several aggregates.
 */
final readonly class EventScheduler
{
    public function __construct(private EventRepository $events)
    {
    }

    public function ensureFree(DateRange $period, ?Ulid $except = null): void
    {
        $overlapping = $this->events->findOverlapping($period, $except);

        if (null !== $overlapping) {
            throw InvalidEvent::overlaps($overlapping->name());
        }
    }
}
