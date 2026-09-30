<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

final class DateRangeEndsBeforeStart extends InvalidDateRange
{
    public function __construct()
    {
        parent::__construct('shared.date_range_ends_before_start');
    }
}
