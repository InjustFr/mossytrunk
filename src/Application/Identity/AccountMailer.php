<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Domain\Identity\PasswordToken;

interface AccountMailer
{
    public function sendInvitation(PasswordToken $passwordToken, string $token): void;

    public function sendPasswordReset(PasswordToken $passwordToken, string $token): void;
}
