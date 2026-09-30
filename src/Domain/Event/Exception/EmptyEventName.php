<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

final class EmptyEventName extends InvalidEvent
{
    public function __construct()
    {
        parent::__construct('event.empty_name');
    }
}
