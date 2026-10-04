<?php

declare(strict_types=1);

namespace App\Application\Event\AddExpense;

use App\Application\Transaction;
use App\Domain\Event\EventRepository;
use App\Domain\Event\ExpenseSpread;
use App\Domain\Shared\Money;
use Symfony\Component\Uid\Ulid;

final readonly class AddExpenseHandler
{
    public function __construct(
        private EventRepository $events,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(AddExpense $command): Ulid
    {
        $event = $this->events->get(Ulid::fromString($command->eventId));
        $expense = $event->addExpense($command->label, Money::cents($command->amountCents), ExpenseSpread::of($command->sharedOverEvents, $command->sharedUntil));
        $this->transaction->commit();

        return $expense->id();
    }
}
