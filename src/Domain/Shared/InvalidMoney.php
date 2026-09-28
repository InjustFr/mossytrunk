<?php

declare(strict_types=1);

namespace App\Domain\Shared;

final class InvalidMoney extends DomainException
{
    public static function mustNotBeNegative(string $what): self
    {
        return new self(\sprintf('%s ne peut pas être négatif.', $what));
    }

    public static function mustBePositive(string $what): self
    {
        return new self(\sprintf('%s doit être supérieur à zéro.', $what));
    }
}
