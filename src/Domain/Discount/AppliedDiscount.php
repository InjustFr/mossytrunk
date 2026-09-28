<?php

declare(strict_types=1);

namespace App\Domain\Discount;

use App\Domain\Shared\Money;

/**
 * A discount granted on an order: a positive `amount` subtracted from the subtotal.
 * Orders keep it as a snapshot (label + amount), independent from later rule changes.
 */
final readonly class AppliedDiscount
{
    public function __construct(
        public string $label,
        public Money $amount,
    ) {
    }

    /**
     * @return array{label: string, amount: int}
     */
    public function toArray(): array
    {
        return ['label' => $this->label, 'amount' => $this->amount->amount()];
    }

    /**
     * @param array{label: string, amount: int} $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['label'], Money::cents($data['amount']));
    }
}
