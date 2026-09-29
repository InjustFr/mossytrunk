<?php

declare(strict_types=1);

namespace App\Domain\Identity;

final readonly class SecretName
{
    private function __construct(public string $value)
    {
    }

    public static function of(string $service, string $field): self
    {
        return self::named($service.'_'.$field);
    }

    public static function named(string $value): self
    {
        if (1 !== preg_match('/^[a-z0-9]+_[a-z0-9_]+$/', $value) || \strlen($value) > 50) {
            throw InvalidAccount::invalidSecretName($value);
        }

        return new self($value);
    }
}
