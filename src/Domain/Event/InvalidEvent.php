<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Shared\DomainException;

final class InvalidEvent extends DomainException
{
    public static function emptyName(): self
    {
        return new self('Le nom de l\'événement est obligatoire.');
    }

    public static function emptyLocation(): self
    {
        return new self('Le lieu de l\'événement est obligatoire.');
    }

    public static function overlaps(string $otherEventName): self
    {
        return new self(\sprintf('Ces dates chevauchent l\'événement « %s ». Deux événements ne peuvent pas avoir lieu en même temps.', $otherEventName));
    }

    public static function ordersOutsidePeriod(int $count): self
    {
        return new self(\sprintf('%d commande(s) de cet événement tomberaient en dehors des nouvelles dates.', $count));
    }

    public static function emptyExpenseLabel(): self
    {
        return new self('Le libellé de la dépense est obligatoire.');
    }

    public static function unknownExpense(string $expenseId): self
    {
        return new self(\sprintf('Dépense introuvable (%s).', $expenseId));
    }
}
