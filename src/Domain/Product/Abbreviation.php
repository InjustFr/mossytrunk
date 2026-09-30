<?php

declare(strict_types=1);

namespace App\Domain\Product;

final class Abbreviation
{
    private const int LENGTH = 3;

    public static function of(string $text, string $fallback): string
    {
        $ascii = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) iconv('UTF-8', 'ASCII//TRANSLIT', $text)));

        return '' === $ascii ? $fallback : substr($ascii, 0, self::LENGTH);
    }
}
