<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Reporting;

use Symfony\Component\Uid\Ulid;

final class SqlValue
{
    public static function int(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    public static function string(mixed $value): string
    {
        return \is_scalar($value) ? (string) $value : '';
    }

    public static function nullableString(mixed $value): ?string
    {
        return null === $value ? null : self::string($value);
    }

    public static function bool(mixed $value): bool
    {
        return true === $value || 1 === $value || 't' === $value || '1' === $value;
    }

    public static function ulid(mixed $value): ?Ulid
    {
        return \is_string($value) ? Ulid::fromString($value) : null;
    }
}
