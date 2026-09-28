<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use App\Domain\Shared\DomainException;

final class InvalidAccount extends DomainException
{
    public static function invalidEmail(string $email): self
    {
        return new self(\sprintf('Adresse email invalide « %s ».', $email));
    }

    public static function emailAlreadyUsed(string $email): self
    {
        return new self(\sprintf('Un utilisateur existe déjà avec l\'adresse « %s ».', $email));
    }

    public static function emptyWorkspaceName(): self
    {
        return new self('Le nom de l\'espace de travail est obligatoire.');
    }

    public static function passwordTooShort(int $minLength): self
    {
        return new self(\sprintf('Le mot de passe doit contenir au moins %d caractères.', $minLength));
    }

    public static function invalidSumUpMerchantCode(string $merchantCode): self
    {
        return new self(\sprintf('Code marchand SumUp invalide « %s » (lettres et chiffres).', $merchantCode));
    }

    public static function emptySecret(): self
    {
        return new self('La clé ne peut pas être vide.');
    }
}
