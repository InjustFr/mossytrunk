<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Domain\Shared\DomainException;

final class InvalidProduct extends DomainException
{
    public static function emptyName(): self
    {
        return new self('Le nom du produit est obligatoire.');
    }

    public static function emptyReference(): self
    {
        return new self('La référence du produit est obligatoire.');
    }

    public static function emptyVariant(): self
    {
        return new self('Une variante ne peut pas être vide.');
    }

    public static function duplicateVariant(string $variant): self
    {
        return new self(\sprintf('La variante « %s » existe déjà pour ce produit.', $variant));
    }

    public static function variantRequired(string $productName): self
    {
        return new self(\sprintf('Choisissez une variante pour « %s ».', $productName));
    }

    public static function unknownVariant(string $productName, string $variant): self
    {
        return new self(\sprintf('« %s » n\'est pas une variante de « %s ».', $variant, $productName));
    }

    public static function hasNoVariants(string $productName): self
    {
        return new self(\sprintf('« %s » est un produit unique : il n\'a pas de variante.', $productName));
    }

    public static function movedOntoItself(): self
    {
        return new self('Choisissez un autre produit que celui d\'origine.');
    }

    public static function soldWithoutVariant(string $productName): self
    {
        return new self(\sprintf('« %s » a déjà des ventes sans variante : déplacez-le d\'abord lui-même vers une variante.', $productName));
    }

    public static function emptyTypeName(): self
    {
        return new self('Le nom du type est obligatoire.');
    }

    public static function invalidTypeColor(string $color): self
    {
        return new self(\sprintf('« %s » n\'est pas une couleur valide (format #rrggbb).', $color));
    }

    public static function typeAlreadyExists(string $name): self
    {
        return new self(\sprintf('Le type « %s » existe déjà.', $name));
    }

    public static function invalidTypeCode(string $code): self
    {
        return new self(\sprintf('Code de type invalide « %s » (1 à 8 lettres majuscules ou chiffres).', $code));
    }

    public static function priceDatedInTheFuture(): self
    {
        return new self('Un prix ne peut pas être daté dans le futur.');
    }

    public static function lastPriceKept(): self
    {
        return new self('Le produit doit garder au moins un prix.');
    }

    public static function negativeLowStockThreshold(): self
    {
        return new self('Le seuil de stock bas ne peut pas être négatif.');
    }
}
