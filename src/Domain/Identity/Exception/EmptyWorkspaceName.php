<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exception;

final class EmptyWorkspaceName extends InvalidAccount
{
    public function __construct()
    {
        parent::__construct('Le nom de l\'espace de travail est obligatoire.');
    }
}
