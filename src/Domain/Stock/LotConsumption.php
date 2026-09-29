<?php

declare(strict_types=1);

namespace App\Domain\Stock;

use App\Domain\Shared\Money;

final readonly class LotConsumption
{
    public function __construct(
        public int $quantity,
        public Money $cost,
    ) {
    }
}
