<?php

declare(strict_types=1);

namespace App\Infrastructure\Connector\Etsy;

use App\Domain\Shared\Money;
use App\Infrastructure\Http\Json;

final class EtsyMoney
{
    public static function of(mixed $amount): Money
    {
        $amount = Json::object($amount);
        $divisor = (int) Json::number($amount['divisor'] ?? 100);

        return Money::cents((int) round((float) Json::number($amount['amount'] ?? 0) * 100 / max(1, $divisor)));
    }
}
