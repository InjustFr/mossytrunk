<?php

declare(strict_types=1);

namespace App\Domain\Event;

enum EventTiming: string
{
    case Upcoming = 'upcoming';
    case Ongoing = 'ongoing';
    case Past = 'past';
}
