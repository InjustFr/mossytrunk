<?php

declare(strict_types=1);

namespace App\Application\SumUp;

use App\Domain\Shared\DomainException;

final class SumUpUnavailable extends DomainException
{
    public static function notConfigured(): self
    {
        return new self('SumUp n\'est pas configuré : renseignez la clé API et le code marchand dans Paramètres.');
    }

    public static function failed(string $reason): self
    {
        return new self(\sprintf('Impossible de joindre SumUp : %s', $reason));
    }
}
