<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exception;

final class PasswordTokenAlreadyUsed extends InvalidPasswordToken
{
    public function __construct()
    {
        parent::__construct('Ce lien a déjà été utilisé.');
    }
}
