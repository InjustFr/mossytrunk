<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

final class ItemParser
{
    private const string SEPARATORS = '/[,;+\n]+/u';
    private const array NOISE = [
        '/\d+(?:[.,]\d{1,2})?\s*(?:€|eur(?:o?s?)?\b)/iu',
        '/€/u',
        '/\b(?:cb|carte|esp(?:[eè]ces?)?|cash|liquide|ch[eè]que|chq|sumup|total|tot|pay[eé]|r[eé]gl[eé]|=)\b/iu',
        '/=/u',
    ];
    private const string LEADING_QUANTITY = '/^(?:[x×*]\s*)?(\d{1,3})\s*[x×*]?\s+(?=\S)/iu';
    private const string TRAILING_QUANTITY = '/\s*[x×*]\s*(\d{1,3})$/iu';
    private const string TRAILING_NUMBER = '/\s+\d+(?:[.,]\d+)?$/u';

    /**
     * @param list<string> $lines
     *
     * @return list<WrittenItem>
     */
    public static function items(array $lines): array
    {
        $items = [];
        foreach (preg_split(self::SEPARATORS, implode("\n", $lines)) ?: [] as $chunk) {
            $item = self::item($chunk);
            if (null !== $item) {
                $items[] = $item;
            }
        }

        return $items;
    }

    private static function item(string $chunk): ?WrittenItem
    {
        $text = trim(preg_replace(self::NOISE, ' ', $chunk) ?? $chunk);
        $quantity = 1;
        if (1 === preg_match(self::LEADING_QUANTITY, $text, $match)) {
            $quantity = (int) $match[1];
            $text = substr($text, \strlen($match[0]));
        } elseif (1 === preg_match(self::TRAILING_QUANTITY, $text, $match)) {
            $quantity = (int) $match[1];
            $text = substr($text, 0, -\strlen($match[0]));
        } else {
            $text = preg_replace(self::TRAILING_NUMBER, '', $text) ?? $text;
        }
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);

        return 1 === preg_match('/\p{L}/u', $text) && $quantity > 0 ? new WrittenItem($text, $quantity) : null;
    }
}
