<?php

declare(strict_types=1);

namespace App\Application\Workspace\GetSettings;

final readonly class SumUpSettingsView
{
    public function __construct(
        public ?string $merchantCode,
        public bool $apiKeyConfigured,
        public ?string $apiKeyHint,
    ) {
    }

    public static function of(?string $merchantCode, ?string $apiKey): self
    {
        return new self($merchantCode, null !== $apiKey, null === $apiKey ? null : '••••'.mb_substr($apiKey, -4));
    }
}
