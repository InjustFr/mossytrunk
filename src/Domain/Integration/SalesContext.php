<?php

declare(strict_types=1);

namespace App\Domain\Integration;

enum SalesContext: string
{
    case AtEvent = 'at_event';
    case Online = 'online';
}
