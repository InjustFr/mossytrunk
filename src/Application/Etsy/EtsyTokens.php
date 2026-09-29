<?php

declare(strict_types=1);

namespace App\Application\Etsy;

final readonly class EtsyTokens
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public \DateTimeImmutable $expiresAt,
    ) {
    }
}
