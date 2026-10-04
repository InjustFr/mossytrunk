<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

final class SharingEndsBeforeEvent extends InvalidEvent
{
    public function __construct()
    {
        parent::__construct('event.sharing_ends_before_event');
    }
}
