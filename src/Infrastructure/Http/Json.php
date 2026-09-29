<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

final class Json
{
    /**
     * @return array<string, mixed>
     */
    public static function object(mixed $value): array
    {
        $object = [];
        foreach (\is_array($value) ? $value : [] as $key => $item) {
            if (\is_string($key)) {
                $object[$key] = $item;
            }
        }

        return $object;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function objects(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }

        return array_values(array_map(self::object(...), array_filter($value, static fn (mixed $item): bool => \is_array($item))));
    }

    public static function string(mixed $value): string
    {
        return \is_scalar($value) ? (string) $value : '';
    }

    public static function number(mixed $value): float|int|string
    {
        return \is_int($value) || \is_float($value) || \is_string($value) ? $value : 0;
    }
}
