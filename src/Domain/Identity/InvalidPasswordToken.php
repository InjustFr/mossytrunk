<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use App\Domain\Shared\DomainException;

final class InvalidPasswordToken extends DomainException
{
    public static function invalid(): self
    {
        return new self('Ce lien n\'est pas valide.');
    }

    public static function alreadyUsed(): self
    {
        return new self('Ce lien a déjà été utilisé.');
    }

    public static function expired(): self
    {
        return new self('Ce lien a expiré. Demandez-en un nouveau depuis « Mot de passe oublié ? ».');
    }
}
