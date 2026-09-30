<?php

declare(strict_types=1);

namespace App\Domain\Identity;

enum Language: string
{
    case English = 'en';
    case French = 'fr';

    public const self DEFAULT = self::English;

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_map(static fn (self $language): string => $language->value, self::cases());
    }
}
