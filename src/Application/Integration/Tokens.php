<?php

declare(strict_types=1);

namespace App\Application\Integration;

final readonly class Tokens
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public \DateTimeImmutable $expiresAt,
    ) {
    }
}
