<?php

declare(strict_types=1);

namespace App\Application\Integration;

use App\Application\Integration\Exception\MissingSetting;

final readonly class Credentials
{
    /**
     * @param array<string, string> $values
     */
    public function __construct(
        public array $values,
        public ?string $accessToken = null,
        public ?string $refreshToken = null,
        public ?string $accountId = null,
    ) {
    }

    public function get(string $name): string
    {
        return $this->values[$name] ?? throw new MissingSetting($name);
    }
}
