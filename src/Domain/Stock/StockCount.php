<?php

declare(strict_types=1);

namespace App\Domain\Stock;

use App\Domain\Shared\Money;

final readonly class StockCount
{
    public function __construct(
        public StockItem $item,
        public int $counted,
        public Money $fallbackUnitCost,
    ) {
    }
}
