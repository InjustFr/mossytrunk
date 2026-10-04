<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Shared\Money;

final class Csv
{
    /**
     * @param list<string> $cells
     */
    public static function row(array $cells, string $separator): string
    {
        $quoted = '/['.preg_quote($separator, '/').'"\r\n]/';

        return implode($separator, array_map(static fn (string $cell): string => 1 === preg_match($quoted, $cell) ? '"'.str_replace('"', '""', $cell).'"' : $cell, $cells));
    }

    public static function amount(Money $money, string $decimalPoint): string
    {
        return number_format($money->amount() / 100, 2, $decimalPoint, '');
    }
}
