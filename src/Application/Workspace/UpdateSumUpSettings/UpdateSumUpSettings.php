<?php

declare(strict_types=1);

namespace App\Application\Workspace\UpdateSumUpSettings;

final readonly class UpdateSumUpSettings
{
    public function __construct(
        public string $merchantCode,
        public ?string $apiKey = null,
    ) {
    }
}
