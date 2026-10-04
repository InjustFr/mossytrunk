<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Shared\Money;

final readonly class ExpenseShare
{
    public function __construct(
        public Expense $expense,
        public Money $amount,
        public int $sharedBy,
    ) {
    }
}
