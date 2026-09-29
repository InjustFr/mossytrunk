<?php

declare(strict_types=1);

namespace App\Domain\Discount;

use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

final readonly class AppliedDiscount
{
    public function __construct(
        public string $label,
        public Money $amount,
        public ?Ulid $ruleId = null,
    ) {
    }

    /**
     * @return array{label: string, amount: int, ruleId: ?string}
     */
    public function toArray(): array
    {
        return ['label' => $this->label, 'amount' => $this->amount->amount(), 'ruleId' => null === $this->ruleId ? null : (string) $this->ruleId];
    }

    /**
     * @param array{label: string, amount: int, ruleId?: ?string} $data
     */
    public static function fromArray(array $data): self
    {
        $ruleId = $data['ruleId'] ?? null;

        return new self($data['label'], Money::cents($data['amount']), null === $ruleId ? null : Ulid::fromString($ruleId));
    }
}
