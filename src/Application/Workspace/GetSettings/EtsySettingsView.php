<?php

declare(strict_types=1);

namespace App\Application\Workspace\GetSettings;

final readonly class EtsySettingsView
{
    public function __construct(
        public ?string $keystring,
        public bool $sharedSecretConfigured,
        public ?string $sharedSecretHint,
        public bool $connected,
        public ?string $shopName,
    ) {
    }

    public static function of(?string $keystring, ?string $sharedSecret, ?string $shopName): self
    {
        return new self($keystring, null !== $sharedSecret, null === $sharedSecret ? null : '••••'.mb_substr($sharedSecret, -4), null !== $shopName, $shopName);
    }
}
