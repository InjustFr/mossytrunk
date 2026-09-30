<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exception;

final class EmailAlreadyUsed extends InvalidAccount
{
    public function __construct(string $email)
    {
        parent::__construct(\sprintf('Un utilisateur existe déjà avec l\'adresse « %s ».', $email));
    }
}
