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

    public static function noCondition(): self
    {
        return new self('Ajoutez au moins une condition à la remise.');
    }

    public static function quantityTooSmall(): self
    {
        return new self('Une condition porte sur au moins 1 article.');
    }

    public static function duplicateTarget(string $targetName): self
    {
        return new self(\sprintf('« %s » apparaît dans plusieurs conditions : regroupez-les en une seule.', $targetName));
    }

    public static function invalidPercentage(): self
    {
        return new self('Le pourcentage de remise doit être compris entre 0 et 100 %.');
    }

    public static function startedToday(): self
    {
        return new self('Cette remise commence aujourd\'hui : modifiez ses dates ou supprimez-la pour l\'arrêter.');
    }

    public static function expired(string $name): self
    {
        return new self(\sprintf('« %s » est expirée : modifiez ses dates pour la relancer.', $name));
    }

    public static function notRunning(string $name): self
    {
        return new self(\sprintf('« %s » n\'est pas en cours.', $name));
    }

    public static function endsBeforeStart(): self
    {
        return new self('La fin de validité ne peut pas précéder son début.');
    }

    public static function onlyEligibleProduct(string $ruleName, string $productName): self
    {
        return new self(\sprintf('La remise « %s » ne concerne que « %s » : modifiez ou supprimez-la avant de supprimer le produit.', $ruleName, $productName));
    }
}
