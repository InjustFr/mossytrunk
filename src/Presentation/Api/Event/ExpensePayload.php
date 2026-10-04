<?php

declare(strict_types=1);

namespace App\Presentation\Api\Event;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ExpensePayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'expense.label.required')]
        #[Assert\Length(max: 255)]
        public string $label = '',
        #[Assert\Positive(message: 'expense.amount.positive')]
        public int $amount = 0,
        #[Assert\GreaterThanOrEqual(2, message: 'expense.shared_over_events.min')]
        public ?int $sharedOverEvents = null,
        #[Assert\Date(message: 'date.invalid')]
        public ?string $sharedUntil = null,
    ) {
    }

    public function until(): ?\DateTimeImmutable
    {
        return null === $this->sharedUntil || '' === $this->sharedUntil ? null : new \DateTimeImmutable($this->sharedUntil);
    }
}
