<?php

declare(strict_types=1);

namespace App\Application\Event\GetEvent;

use App\Domain\Event\Event;
use App\Domain\Event\Expense;

final readonly class EventView
{
    /**
     * @param list<array{id: string, label: string, amount: int}> $expenses
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $location,
        public string $startDate,
        public string $endDate,
        public array $expenses,
        public int $expensesTotal,
    ) {
    }

    public static function fromEvent(Event $event): self
    {
        return new self(
            (string) $event->id(),
            $event->name(),
            $event->location(),
            $event->period()->start()->format('Y-m-d'),
            $event->period()->end()->format('Y-m-d'),
            array_map(static fn (Expense $expense): array => [
                'id' => (string) $expense->id(),
                'label' => $expense->label(),
                'amount' => $expense->amount()->amount(),
            ], $event->expenses()),
            $event->totalExpenses()->amount(),
        );
    }
}
