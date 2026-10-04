<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class Money
{
    private function __construct(
        #[ORM\Column(type: 'integer')]
        private int $cents,
    ) {
    }

    public static function cents(int $cents): self
    {
        return new self($cents);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    /**
     * @param iterable<self> $amounts
     */
    public static function sum(iterable $amounts): self
    {
        $total = 0;
        foreach ($amounts as $amount) {
            $total += $amount->cents;
        }

        return new self($total);
    }

    public function amount(): int
    {
        return $this->cents;
    }

    public function add(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function subtract(self $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function multiply(int $factor): self
    {
        return new self($this->cents * $factor);
    }

    public function percentage(int $basisPoints): self
    {
        return $this->prorate($basisPoints, 10_000);
    }

    public function prorate(int $part, int $whole): self
    {
        return new self((int) round($this->cents * $part / $whole, 0, \PHP_ROUND_HALF_UP));
    }

    public function isZero(): bool
    {
        return 0 === $this->cents;
    }

    public function isNegative(): bool
    {
        return $this->cents < 0;
    }

    public function isPositive(): bool
    {
        return $this->cents > 0;
    }

    public function greaterThan(self $other): bool
    {
        return $this->cents > $other->cents;
    }

    public function lessThan(self $other): bool
    {
        return $this->cents < $other->cents;
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents;
    }
}
