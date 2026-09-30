<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exception;

final class PasswordTooShort extends InvalidAccount
{
    public function __construct(int $minLength)
    {
        parent::__construct(\sprintf('Le mot de passe doit contenir au moins %d caractères.', $minLength));
    }
}
