<?php

declare(strict_types=1);

namespace App\Domain\Sales;

use App\Domain\Shared\Money;

final readonly class PriceAdjustment
{
    private function __construct(
        private int $cents,
        private int $basisPoints,
    ) {
    }

    public static function none(): self
    {
        return new self(0, 0);
    }

    public static function byCents(int $cents): self
    {
        return new self($cents, 0);
    }

    public static function byBasisPoints(int $basisPoints): self
    {
        return new self(0, $basisPoints);
    }

    public function applyTo(Money $price): Money
    {
        return $price->add($price->percentage($this->basisPoints))->add(Money::cents($this->cents));
    }
}
