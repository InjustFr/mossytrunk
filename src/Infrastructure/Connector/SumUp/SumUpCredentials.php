<?php

declare(strict_types=1);

namespace App\Infrastructure\Connector\SumUp;

final readonly class SumUpCredentials
{
    public function __construct(
        public string $apiKey,
        public string $merchantCode,
    ) {
    }
}
