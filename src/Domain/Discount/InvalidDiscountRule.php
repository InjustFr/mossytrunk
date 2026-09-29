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
        return new self('Choisissez au moins un type ou un produit concerné par la remise.');
    }

    public static function onlyEligibleProduct(string $ruleName, string $productName): self
    {
        return new self(\sprintf('La remise « %s » ne concerne que « %s » : modifiez ou supprimez-la avant de supprimer le produit.', $ruleName, $productName));
    }

    public static function bundleTooSmall(): self
    {
        return new self('Un lot doit contenir au moins 2 articles.');
    }
}
