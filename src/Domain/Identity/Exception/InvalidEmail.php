<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exception;

final class InvalidEmail extends InvalidAccount
{
    public function __construct(string $email)
    {
        parent::__construct(\sprintf('Adresse email invalide « %s ».', $email));
    }
}
