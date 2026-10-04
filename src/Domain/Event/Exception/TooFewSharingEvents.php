<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

final class TooFewSharingEvents extends InvalidEvent
{
    public function __construct()
    {
        parent::__construct('event.too_few_sharing_events');
    }
}
