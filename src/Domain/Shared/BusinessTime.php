<?php

declare(strict_types=1);

namespace App\Domain\Shared;

final class BusinessTime
{
    public const string ZONE = 'Europe/Paris';

    public static function at(string $localTime): \DateTimeImmutable
    {
        return new \DateTimeImmutable($localTime, new \DateTimeZone(self::ZONE));
    }

    public static function local(\DateTimeImmutable $moment): \DateTimeImmutable
    {
        return $moment->setTimezone(new \DateTimeZone(self::ZONE));
    }

    public static function day(\DateTimeImmutable $moment): string
    {
        return self::local($moment)->format('Y-m-d');
    }

    public static function month(\DateTimeImmutable $moment): string
    {
        return self::local($moment)->format('Y-m');
    }

    public static function atom(\DateTimeImmutable $moment): string
    {
        return self::local($moment)->format(\DATE_ATOM);
    }

    public static function midnightOf(\DateTimeImmutable $moment): \DateTimeImmutable
    {
        return self::at(self::day($moment));
    }
}
