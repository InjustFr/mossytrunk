<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exception;

final class PasswordTokenExpired extends InvalidPasswordToken
{
    public function __construct()
    {
        parent::__construct('Ce lien a expiré. Demandez-en un nouveau depuis « Mot de passe oublié ? ».');
    }
}
