<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

final class DateRangeEndsBeforeStart extends InvalidDateRange
{
    public function __construct()
    {
        parent::__construct('La date de fin doit être postérieure ou égale à la date de début.');
    }
}
