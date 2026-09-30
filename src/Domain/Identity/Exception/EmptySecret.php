<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exception;

final class EmptySecret extends InvalidAccount
{
    public function __construct()
    {
        parent::__construct('identity.empty_secret');
    }
}
