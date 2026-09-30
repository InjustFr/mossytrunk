<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exception;

final class InvalidSecretName extends InvalidAccount
{
    public function __construct(string $name)
    {
        parent::__construct('identity.invalid_secret_name', ['name' => $name]);
    }
}
