<?php

declare(strict_types=1);

namespace App\Application\Event\ReviseExpense;

use App\Application\Transaction;
use App\Domain\Event\EventRepository;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

final readonly class ReviseExpenseHandler
{
    public function __construct(
        private EventRepository $events,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(ReviseExpense $command): void
    {
        $this->events->get(Ulid::fromString($command->eventId))
            ->reviseExpense(Ulid::fromString($command->expenseId), $command->label, Money::cents($command->amountCents));

        $this->transaction->commit();
    }
}
