<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

final class EmptyExpenseLabel extends InvalidEvent
{
    public function __construct()
    {
        parent::__construct('event.empty_expense_label');
    }
}
