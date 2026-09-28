<?php

declare(strict_types=1);

namespace App\Application\Identity\CreateUser;

final readonly class CreateUser
{
    public function __construct(
        public string $email,
        public string $workspaceName,
    ) {
    }
}
