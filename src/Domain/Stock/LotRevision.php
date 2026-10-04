<?php

declare(strict_types=1);

namespace App\Domain\Stock;

use App\Domain\Shared\Money;

final readonly class LotRevision
{
    public function __construct(
        private Money $previousTotal,
        private int $previousQuantity,
        private Money $total,
        private int $quantity,
    ) {
    }

    public function changesUnitCost(): bool
    {
        return $this->previousTotal->amount() * $this->quantity !== $this->total->amount() * $this->previousQuantity;
    }

    public function drewFrom(int $units, Money $cost): bool
    {
        $cheapest = intdiv($this->previousTotal->amount(), $this->previousQuantity);
        $dearest = $cheapest + (0 === $this->previousTotal->amount() % $this->previousQuantity ? 0 : 1);

        return $units > 0 && $cost->amount() >= $units * $cheapest && $cost->amount() <= $units * $dearest;
    }

    public function costOf(int $units): Money
    {
        return $this->total->prorate($units, $this->quantity);
    }
}
