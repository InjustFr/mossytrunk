<?php

declare(strict_types=1);

namespace App\Domain\Sales;

use App\Domain\Shared\Money;

final readonly class OrderCharge
{
    public function __construct(
        public string $label,
        public Money $amount,
    ) {
    }

    /**
     * @param array{label: string, amount: int} $charge
     */
    public static function fromArray(array $charge): self
    {
        return new self($charge['label'], Money::cents($charge['amount']));
    }

    /**
     * @return array{label: string, amount: int}
     */
    public function toArray(): array
    {
        return ['label' => $this->label, 'amount' => $this->amount->amount()];
    }
}
