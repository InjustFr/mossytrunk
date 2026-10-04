<?php

declare(strict_types=1);

namespace App\Domain\Reference;

use App\Domain\Shared\BusinessTime;

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
        return new self(BusinessTime::local($moment), '', '');
    }

    public static function named(\DateTimeImmutable $moment, string $typeCode, string $nameCode): self
    {
        return new self(BusinessTime::local($moment), $typeCode, $nameCode);
    }
}
