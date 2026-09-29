<?php

declare(strict_types=1);

namespace App\Domain\Accounting;

enum DeclarationPeriodicity: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';

    public function months(): int
    {
        return match ($this) {
            self::Monthly => 1,
            self::Quarterly => 3,
        };
    }
}
