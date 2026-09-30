<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exception;

final class PasswordTooShort extends InvalidAccount
{
    public function __construct(int $minLength)
    {
        parent::__construct('identity.password_too_short', ['min' => $minLength]);
    }
}
