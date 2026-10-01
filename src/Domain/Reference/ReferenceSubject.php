<?php

declare(strict_types=1);

namespace App\Domain\Reference;

use App\Domain\Shared\DateRange;

final readonly class ReferenceSubject
{
    private function __construct(
        public \DateTimeImmutable $moment,
        public string $typeCode,
        public string $nameCode,
    ) {
    }

    public static function at(\DateTimeImmutable $moment): self
    {
        return new self(self::local($moment), '', '');
    }

    public static function named(\DateTimeImmutable $moment, string $typeCode, string $nameCode): self
    {
        return new self(self::local($moment), $typeCode, $nameCode);
    }

    private static function local(\DateTimeImmutable $moment): \DateTimeImmutable
    {
        return $moment->setTimezone(new \DateTimeZone(DateRange::TIMEZONE));
    }
}
