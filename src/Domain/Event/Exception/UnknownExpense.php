<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

final class UnknownExpense extends InvalidEvent
{
    public function __construct(string $expenseId)
    {
        parent::__construct(\sprintf('Dépense introuvable (%s).', $expenseId));
    }
}
