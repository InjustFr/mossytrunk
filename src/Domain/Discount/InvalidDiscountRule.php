<?php

declare(strict_types=1);

namespace App\Domain\Discount;

use App\Domain\Shared\DomainException;

final class InvalidDiscountRule extends DomainException
{
    public static function emptyName(): self
    {
        return new self('Le nom de la remise est obligatoire.');
    }

    public static function noEligibleProduct(): self
    {
        return new self('Choisissez au moins un produit concerné par la remise.');
    }

    public static function bundleTooSmall(): self
    {
        return new self('Un lot doit contenir au moins 2 articles.');
    }
}
