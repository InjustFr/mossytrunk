<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

final class NotFound extends DomainException
{
    public function __construct(string $label, string $id)
    {
        parent::__construct(\sprintf('%s introuvable (%s).', $label, $id));
    }
}
