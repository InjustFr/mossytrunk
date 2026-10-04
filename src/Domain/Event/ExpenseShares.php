<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Shared\CostAllocation;
use App\Domain\Shared\Money;

final readonly class ExpenseShares
{
    /**
     * @param array<string, list<ExpenseShare>> $byEvent
     */
    private function __construct(private array $byEvent)
    {
    }

    /**
     * @param list<Event> $events
     */
    public static function among(array $events): self
    {
        usort($events, static fn (Event $a, Event $b): int => $a->period()->start() <=> $b->period()->start());

        $byEvent = [];
        foreach ($events as $index => $event) {
            foreach ($event->expenses() as $expense) {
                $sharers = [$event, ...$expense->spread()->isShared() ? $expense->spread()->keep(\array_slice($events, $index + 1)) : []];
                $amounts = CostAllocation::equally($expense->amount(), \count($sharers));
                foreach ($sharers as $position => $sharer) {
                    $byEvent[(string) $sharer->id()][] = new ExpenseShare($expense, $amounts[$position], \count($sharers));
                }
            }
        }

        return new self($byEvent);
    }

    /**
     * @return list<ExpenseShare>
     */
    public function of(Event $event): array
    {
        return $this->byEvent[(string) $event->id()] ?? [];
    }

    public function totalOf(Event $event): Money
    {
        return Money::sum(array_map(static fn (ExpenseShare $share): Money => $share->amount, $this->of($event)));
    }
}
