<?php

declare(strict_types=1);

namespace App\Domain\Sales;

use App\Domain\Shared\Money;

enum ChannelCostKind: string
{
    case Fixed = 'fixed';
    case Percent = 'percent';

    public function on(int $amount, Money $orderTotal): Money
    {
        return match ($this) {
            self::Fixed => Money::cents($amount),
            self::Percent => $orderTotal->percentage($amount),
        };
    }
}
