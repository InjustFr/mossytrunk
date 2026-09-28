<?php

declare(strict_types=1);

namespace App\Application\Event\ListEvents;

use App\Domain\Event\Event;

final readonly class EventSummaryView
{
    public function __construct(
        public string $id,
        public string $name,
        public string $location,
        public string $startDate,
        public string $endDate,
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
            $event->totalExpenses()->amount(),
        );
    }
}
