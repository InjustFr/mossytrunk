<?php

declare(strict_types=1);

namespace App\Application\Event\RemoveExpense;

final readonly class RemoveExpense
{
    public function __construct(
        public string $eventId,
        public string $expenseId,
    ) {
    }
}
