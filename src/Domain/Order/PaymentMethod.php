<?php

declare(strict_types=1);

namespace App\Domain\Order;

enum PaymentMethod: string
{
    case Card = 'card';
    case Cash = 'cash';
    case Mixed = 'mixed';

    public static function combined(?self $a, ?self $b): ?self
    {
        return $a === $b || null === $b ? $a : (null === $a ? $b : self::Mixed);
    }
}
