<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exception;

final class UnknownPasswordToken extends InvalidPasswordToken
{
    public function __construct()
    {
        parent::__construct('identity.unknown_password_token');
    }
}
