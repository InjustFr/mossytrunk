<?php

declare(strict_types=1);

namespace App\Domain\Stock;

use App\Domain\Shared\DomainException;

final class InvalidStock extends DomainException
{
    public static function quantityMustBePositive(): self
    {
        return new self('La quantité doit être supérieure à zéro.');
    }

    public static function countMustNotBeNegative(): self
    {
        return new self('La quantité comptée ne peut pas être négative.');
    }

    public static function emptyCheck(): self
    {
        return new self('L\'inventaire doit compter au moins un article.');
    }

    public static function countedTwice(string $label): self
    {
        return new self(\sprintf('« %s » est compté deux fois.', $label));
    }

    public static function nothingToResolve(): self
    {
        return new self('Cet écart est déjà expliqué.');
    }
}
