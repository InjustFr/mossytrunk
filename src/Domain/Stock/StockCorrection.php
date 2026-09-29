<?php

declare(strict_types=1);

namespace App\Domain\Stock;

use App\Domain\Shared\Money;

final readonly class StockCorrection
{
    public function __construct(
        public int $expected,
        public int $counted,
        public Money $lossCost,
    ) {
    }

    public function missing(): int
    {
        return max(0, $this->expected - $this->counted);
    }

    public function surplus(): int
    {
        return max(0, $this->counted - $this->expected);
    }
}
