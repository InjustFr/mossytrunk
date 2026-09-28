<?php

declare(strict_types=1);

namespace App\Application\Event\AddExpense;

final readonly class AddExpense
{
    public function __construct(
        public string $eventId,
        public string $label,
        public int $amountCents,
    ) {
    }
}
