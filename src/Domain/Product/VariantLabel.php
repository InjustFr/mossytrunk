<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Domain\Product\Exception\EmptyVariant;

final class VariantLabel
{
    public static function clean(string $label): string
    {
        $label = trim($label);
        if ('' === $label) {
            throw new EmptyVariant();
        }

        return $label;
    }

    public static function display(string $name, ?string $variant): string
    {
        return null === $variant ? $name : \sprintf('%s — %s', $name, $variant);
    }

    public static function same(?string $a, ?string $b): bool
    {
        return null === $a || null === $b ? $a === $b : mb_strtolower(trim($a)) === mb_strtolower(trim($b));
    }

    /**
     * @param list<string> $labels
     */
    public static function find(array $labels, string $label): ?string
    {
        return array_find($labels, static fn (string $existing): bool => self::same($existing, $label));
    }

    /**
     * @param list<string> $labels
     *
     * @return list<string>
     */
    public static function renamed(array $labels, string $from, string $to): array
    {
        return array_map(static fn (string $label): string => self::same($label, $from) ? $to : $label, $labels);
    }
}
