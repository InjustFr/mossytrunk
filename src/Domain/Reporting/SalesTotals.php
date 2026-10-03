<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Domain\Shared\Money;

/**
 * Sums of the figures every order records when it changes, over a set of orders.
 */
final readonly class SalesTotals
{
    public function __construct(
        public int $orderCount,
        public Money $grossSales,
        public Money $discounts,
        public Money $shipping,
        public Money $costOfGoods,
        public Money $supplies,
        public Money $channelCosts,
    ) {
    }

    public static function zero(): self
    {
        return new self(0, Money::zero(), Money::zero(), Money::zero(), Money::zero(), Money::zero(), Money::zero());
    }

    public function turnover(): Money
    {
        return $this->grossSales->subtract($this->discounts)->add($this->shipping);
    }

    public function add(self $other): self
    {
        return new self(
            $this->orderCount + $other->orderCount,
            $this->grossSales->add($other->grossSales),
            $this->discounts->add($other->discounts),
            $this->shipping->add($other->shipping),
            $this->costOfGoods->add($other->costOfGoods),
            $this->supplies->add($other->supplies),
            $this->channelCosts->add($other->channelCosts),
        );
    }
}
