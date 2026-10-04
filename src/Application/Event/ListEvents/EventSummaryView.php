<?php

declare(strict_types=1);

namespace App\Application\Event\ListEvents;

use App\Domain\Event\Event;
use App\Domain\Event\EventTiming;
use App\Domain\Reporting\SalesFigures;

final readonly class EventSummaryView
{
    public function __construct(
        public string $id,
        public string $name,
        public string $location,
        public string $startDate,
        public string $endDate,
        public int $days,
        public int $expensesTotal,
        public int $orderCount,
        public int $turnover,
        public int $costOfGoods,
        public int $urssaf,
        public int $result,
        public string $timing,
        public int $unexplainedUnits = 0,
    ) {
    }

    public static function of(Event $event, SalesFigures $result, EventTiming $timing, int $unexplainedUnits = 0): self
    {
        return new self(
            (string) $event->id(),
            $event->name(),
            $event->location(),
            $event->period()->start()->format('Y-m-d'),
            $event->period()->end()->format('Y-m-d'),
            $event->period()->days(),
            $result->expenses->amount(),
            $result->orderCount,
            $result->turnover->amount(),
            $result->costOfGoods->amount(),
            $result->urssaf->amount(),
            $result->result->amount(),
            $timing->value,
            $unexplainedUnits,
        );
    }
}
