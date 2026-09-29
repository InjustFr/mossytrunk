<?php

declare(strict_types=1);

namespace App\Application\Etsy;

use App\Domain\Shared\DomainException;

final class EtsyUnavailable extends DomainException
{
    public static function notConfigured(): self
    {
        return new self('Renseignez d\'abord les clés de votre application Etsy dans les paramètres.');
    }

    public static function notConnected(): self
    {
        return new self('Aucune boutique Etsy n\'est connectée : connectez-la depuis les paramètres.');
    }

    public static function failed(string $reason): self
    {
        return new self(\sprintf('Etsy ne répond pas comme prévu : %s', $reason));
    }
}
