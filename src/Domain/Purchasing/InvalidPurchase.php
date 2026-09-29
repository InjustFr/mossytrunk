<?php

declare(strict_types=1);

namespace App\Domain\Purchasing;

use App\Domain\Shared\DomainException;

final class InvalidPurchase extends DomainException
{
    public static function emptySupplierName(): self
    {
        return new self('Le nom du fournisseur est obligatoire.');
    }

    public static function supplierAlreadyExists(string $name): self
    {
        return new self(\sprintf('Le fournisseur « %s » existe déjà.', $name));
    }

    public static function emptyOrder(): self
    {
        return new self('Ajoutez au moins un produit à la commande.');
    }

    public static function quantityMustBePositive(): self
    {
        return new self('La quantité commandée doit être d\'au moins 1.');
    }

    public static function orderedTwice(string $label): self
    {
        return new self(\sprintf('« %s » figure deux fois dans la commande.', $label));
    }

    public static function alreadyReceived(string $reference): self
    {
        return new self(\sprintf('La commande %s est déjà réceptionnée : elle ne peut plus changer.', $reference));
    }

    public static function receivedQuantityMissing(string $label): self
    {
        return new self(\sprintf('Indiquez la quantité reçue pour « %s ».', $label));
    }

    public static function receivedQuantityNegative(string $label): self
    {
        return new self(\sprintf('La quantité reçue de « %s » ne peut pas être négative.', $label));
    }
}
