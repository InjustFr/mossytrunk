<?php

declare(strict_types=1);

namespace App\Domain\Accounting;

use App\Domain\Shared\DomainException;

final class InvalidDeclaration extends DomainException
{
    public static function unknownPeriod(string $key): self
    {
        return new self(\sprintf('Période de déclaration inconnue « %s ».', $key));
    }

    public static function periodNotOver(string $key): self
    {
        return new self(\sprintf('La période %s n\'est pas terminée : elle ne peut pas encore être déclarée.', $key));
    }

    public static function invalidRange(): self
    {
        return new self('La fin de la période doit suivre son début.');
    }
}
