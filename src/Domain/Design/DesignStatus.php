<?php

declare(strict_types=1);

namespace App\Domain\Design;

enum DesignStatus: string
{
    case InProgress = 'in_progress';
    case Validated = 'validated';
}
