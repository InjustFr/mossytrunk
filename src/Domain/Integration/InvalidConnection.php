<?php

declare(strict_types=1);

namespace App\Domain\Integration;

use App\Domain\Shared\DomainException;

final class InvalidConnection extends DomainException
{
    public static function invalidService(string $service): self
    {
        return new self(\sprintf('Service inconnu « %s ».', $service));
    }

    public static function alreadyAdded(string $label): self
    {
        return new self(\sprintf('%s est déjà ajouté.', $label));
    }

    public static function invalidSetting(string $name): self
    {
        return new self(\sprintf('Paramètre invalide « %s ».', $name));
    }
}
