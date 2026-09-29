<?php

declare(strict_types=1);

namespace App\Application\Integration;

final readonly class Authorization
{
    public function __construct(
        public Tokens $tokens,
        public string $accountId,
        public string $accountName,
    ) {
    }
}
