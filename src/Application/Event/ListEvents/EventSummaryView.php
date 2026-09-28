<?php

declare(strict_types=1);

namespace App\Application\Event\ListEvents;

use App\Domain\Event\Event;
use App\Domain\Reporting\EventResult;

final readonly class EventSummaryView
{
    public function __construct(
        public string $id,
        public string $name,
        public string $location,
        public string $startDate,
        public string $endDate,
        public int $expensesTotal,
        public int $orderCount,
        public int $turnover,
        public int $result,
    ) {
    }

    public static function of(Event $event, EventResult $result): self
    {
        return new self(
            (string) $event->id(),
            $event->name(),
            $event->location(),
            $event->period()->start()->format('Y-m-d'),
            $event->period()->end()->format('Y-m-d'),
            $event->totalExpenses()->amount(),
            $result->orderCount,
            $result->turnover->amount(),
            $result->result->amount(),
        );
    }
}
