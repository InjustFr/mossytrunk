<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

final class Words
{
    /**
     * @return list<string>
     */
    public static function of(string $text): array
    {
        $decomposed = \Normalizer::normalize(mb_strtolower($text), \Normalizer::FORM_D);
        $plain = preg_replace('/\p{Mn}+/u', '', \is_string($decomposed) ? $decomposed : '') ?? '';
        $words = preg_split('/[^a-z0-9]+/', $plain, flags: \PREG_SPLIT_NO_EMPTY) ?: [];

        return array_map(self::singular(...), $words);
    }

    public static function alike(string $written, string $known): bool
    {
        if ($written === $known) {
            return true;
        }
        $length = min(\strlen($written), \strlen($known));
        $tolerated = match (true) {
            $length >= 7 => 2,
            $length >= 4 => 1,
            default => 0,
        };

        return $tolerated > 0 && levenshtein($written, $known) <= $tolerated;
    }

    private static function singular(string $word): string
    {
        return \strlen($word) > 3 && preg_match('/[sx]$/', $word) ? substr($word, 0, -1) : $word;
    }
}
