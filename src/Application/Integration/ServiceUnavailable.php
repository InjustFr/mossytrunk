<?php

declare(strict_types=1);

namespace App\Application\Integration;

use App\Domain\Shared\DomainException;

final class ServiceUnavailable extends DomainException
{
    public static function unknown(string $service): self
    {
        return new self(\sprintf('Service inconnu « %s ».', $service));
    }

    public static function notAdded(string $label): self
    {
        return new self(\sprintf('%s n\'est pas ajouté : ajoutez-le dans Paramètres › Services connectés.', $label));
    }

    public static function notConfigured(string $label): self
    {
        return new self(\sprintf('%s n\'est pas configuré : complétez ses accès dans Paramètres › Services connectés.', $label));
    }

    public static function notConnected(string $label): self
    {
        return new self(\sprintf('%s n\'est pas connecté : connectez-le depuis Paramètres › Services connectés.', $label));
    }

    public static function notAuthorizing(string $label): self
    {
        return new self(\sprintf('%s ne se connecte pas par autorisation.', $label));
    }

    public static function missingSetting(string $name): self
    {
        return new self(\sprintf('Paramètre manquant « %s ».', $name));
    }

    public static function failed(string $label, string $reason): self
    {
        return new self(\sprintf('Impossible de joindre %s : %s', $label, $reason));
    }
}
