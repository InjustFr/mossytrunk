<?php

declare(strict_types=1);

namespace App\Domain\Design;

use App\Domain\Shared\DomainException;

final class InvalidDesign extends DomainException
{
    public static function emptyName(string $what): self
    {
        return new self(\sprintf('Le nom %s est obligatoire.', $what));
    }

    public static function gabaritAlreadyExists(string $name): self
    {
        return new self(\sprintf('Le gabarit « %s » existe déjà.', $name));
    }

    public static function emptyAdaptation(): self
    {
        return new self('Une adaptation ne peut pas être vide.');
    }

    public static function duplicateAdaptation(string $adaptation): self
    {
        return new self(\sprintf('L\'adaptation « %s » est en double.', $adaptation));
    }

    public static function alreadyDeclined(string $gabarit): self
    {
        return new self(\sprintf('Ce design est déjà décliné en « %s ».', $gabarit));
    }

    public static function unknownAdaptation(string $adaptation): self
    {
        return new self(\sprintf('Adaptation inconnue « %s ».', $adaptation));
    }

    public static function alreadyValidated(string $design): self
    {
        return new self(\sprintf('Le design « %s » est validé : ses déclinaisons ne changent plus.', $design));
    }

    public static function nothingToValidate(string $design): self
    {
        return new self(\sprintf('Déclinez « %s » sur au moins un gabarit avant de le valider.', $design));
    }

    public static function adaptationsPending(string $declination, int $count): self
    {
        return new self(\sprintf('« %s » a encore %d adaptation%s à faire.', $declination, $count, $count > 1 ? 's' : ''));
    }

    public static function sameProductTwice(string $name): self
    {
        return new self(\sprintf('Deux déclinaisons donneraient le même produit « %s » : renommez-en une.', $name));
    }
}
