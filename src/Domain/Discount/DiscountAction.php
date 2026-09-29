<?php

declare(strict_types=1);

namespace App\Domain\Discount;

use App\Domain\Shared\InvalidMoney;
use App\Domain\Shared\Money;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class DiscountAction
{
    private const int WHOLE = 10_000;

    private function __construct(
        #[ORM\Column(length: 16, enumType: DiscountActionKind::class)]
        private DiscountActionKind $kind,
        #[ORM\Column]
        private int $value,
    ) {
    }

    public static function fixedPrice(Money $price): self
    {
        return new self(DiscountActionKind::FixedPrice, self::positive($price, 'Le prix'));
    }

    public static function amountOff(Money $amount): self
    {
        return new self(DiscountActionKind::AmountOff, self::positive($amount, 'La remise'));
    }

    public static function percentOff(int $basisPoints): self
    {
        if ($basisPoints <= 0 || $basisPoints > self::WHOLE) {
            throw InvalidDiscountRule::invalidPercentage();
        }

        return new self(DiscountActionKind::PercentOff, $basisPoints);
    }

    public static function of(DiscountActionKind $kind, int $value): self
    {
        return match ($kind) {
            DiscountActionKind::FixedPrice => self::fixedPrice(Money::cents($value)),
            DiscountActionKind::AmountOff => self::amountOff(Money::cents($value)),
            DiscountActionKind::PercentOff => self::percentOff($value),
        };
    }

    public function saving(Money $regular): Money
    {
        return match ($this->kind) {
            DiscountActionKind::FixedPrice => $regular->subtract(Money::cents($this->value)),
            DiscountActionKind::AmountOff => Money::cents(min($this->value, $regular->amount())),
            DiscountActionKind::PercentOff => $regular->percentage($this->value),
        };
    }

    public function kind(): DiscountActionKind
    {
        return $this->kind;
    }

    public function value(): int
    {
        return $this->value;
    }

    private static function positive(Money $amount, string $label): int
    {
        if (!$amount->isPositive()) {
            throw InvalidMoney::mustBePositive($label);
        }

        return $amount->amount();
    }
}
