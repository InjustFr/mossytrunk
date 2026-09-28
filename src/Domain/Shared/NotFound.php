<?php

declare(strict_types=1);

namespace App\Domain\Shared;

final class NotFound extends DomainException
{
    public static function entity(string $label, string $id): self
    {
        return new self(\sprintf('%s introuvable (%s).', $label, $id));
    }
}
