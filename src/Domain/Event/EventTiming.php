<?php

declare(strict_types=1);

namespace App\Domain\Event;

/**
 * Where an event stands relative to a given day (Europe/Paris).
 */
enum EventTiming: string
{
    case Upcoming = 'upcoming';
    case Ongoing = 'ongoing';
    case Past = 'past';
}
