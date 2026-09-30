<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

final class OverlappingEvent extends InvalidEvent
{
    public function __construct(string $otherEventName)
    {
        parent::__construct(\sprintf('Ces dates chevauchent l\'événement « %s ». Deux événements ne peuvent pas avoir lieu en même temps.', $otherEventName));
    }
}
