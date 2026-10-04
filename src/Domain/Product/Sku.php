<?php

declare(strict_types=1);

namespace App\Domain\Product;

final class Sku
{
    public const string SEPARATOR = '-';

    public static function of(string $reference, ?string $variant): string
    {
        return null === $variant ? $reference : $reference.self::SEPARATOR.$variant;
    }
}
