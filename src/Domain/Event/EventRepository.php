<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Shared\DateRange;
use Symfony\Component\Uid\Ulid;

interface EventRepository
{
    public function add(Event $event): void;

    /**
     * @throws \App\Domain\Shared\NotFound
     */
    public function get(Ulid $id): Event;

    /**
     * The event whose period covers the given moment (Europe/Paris day), if any.
     */
    public function findCovering(\DateTimeImmutable $moment): ?Event;

    /**
     * First event overlapping the period, ignoring $except (the event being rescheduled).
     */
    public function findOverlapping(DateRange $period, ?Ulid $except = null): ?Event;

    /**
     * @return list<Event> most recent first
     */
    public function all(): array;
}
