<?php

declare(strict_types=1);

namespace App\Domain\Shared;

final class InvalidDateRange extends DomainException
{
    public static function endBeforeStart(): self
    {
        return new self('La date de fin doit être postérieure ou égale à la date de début.');
    }
}
