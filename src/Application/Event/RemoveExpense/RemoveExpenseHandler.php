<?php

declare(strict_types=1);

namespace App\Application\Event\RemoveExpense;

use App\Application\Transaction;
use App\Domain\Event\EventRepository;
use Symfony\Component\Uid\Ulid;

final readonly class RemoveExpenseHandler
{
    public function __construct(
        private EventRepository $events,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(RemoveExpense $command): void
    {
        $event = $this->events->get(Ulid::fromString($command->eventId));
        $event->removeExpense(Ulid::fromString($command->expenseId));
        $this->transaction->commit();
    }
}
