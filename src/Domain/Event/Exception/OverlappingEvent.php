<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

final class OverlappingEvent extends InvalidEvent
{
    public function __construct(string $otherEventName)
    {
        parent::__construct('event.overlapping', ['name' => $otherEventName]);
    }
}
