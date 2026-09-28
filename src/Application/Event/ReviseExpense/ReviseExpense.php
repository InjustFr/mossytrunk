<?php

declare(strict_types=1);

namespace App\Application\Event\ReviseExpense;

final readonly class ReviseExpense
{
    public function __construct(
        public string $eventId,
        public string $expenseId,
        public string $label,
        public int $amountCents,
    ) {
    }
}
