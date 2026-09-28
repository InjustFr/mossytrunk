<?php

declare(strict_types=1);

namespace App\Domain\Order;

use App\Domain\Shared\DateRange;
use App\Domain\Shared\DomainException;

final class InvalidOrder extends DomainException
{
    public static function outsideEvent(string $eventName, \DateTimeImmutable $placedAt): self
    {
        return new self(\sprintf(
            'La date de la commande (%s) doit être comprise dans les dates de l\'événement « %s ».',
            self::local($placedAt)->format('d/m/Y H:i'),
            $eventName,
        ));
    }

    public static function noEventAt(\DateTimeImmutable $placedAt): self
    {
        return new self(\sprintf(
            'Aucun événement le %s. Créez d\'abord l\'événement correspondant.',
            self::local($placedAt)->format('d/m/Y'),
        ));
    }

    public static function empty(): self
    {
        return new self('Une commande doit contenir au moins un produit.');
    }

    public static function invalidQuantity(): self
    {
        return new self('La quantité doit être d\'au moins 1.');
    }

    public static function discountExceedsSubtotal(): self
    {
        return new self('Les remises ne peuvent pas dépasser le montant de la commande.');
    }

    private static function local(\DateTimeImmutable $moment): \DateTimeImmutable
    {
        return $moment->setTimezone(new \DateTimeZone(DateRange::TIMEZONE));
    }
}
