<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Event\Exception\TooFewSharingEvents;

final readonly class ExpenseSpread
{
    private function __construct(
        public ?int $events,
        public ?\DateTimeImmutable $until,
    ) {
    }

    public static function none(): self
    {
        return new self(null, null);
    }

    public static function of(?int $events, ?\DateTimeImmutable $until): self
    {
        if (null !== $events && $events < 2) {
            throw new TooFewSharingEvents();
        }

        return new self($events, $until?->setTime(0, 0));
    }

    public function isShared(): bool
    {
        return null !== $this->events || null !== $this->until;
    }

    public function endsBefore(\DateTimeImmutable $day): bool
    {
        return null !== $this->until && $this->until->format('Y-m-d') < $day->format('Y-m-d');
    }

    /**
     * @param list<Event> $following
     *
     * @return list<Event>
     */
    public function keep(array $following): array
    {
        $kept = array_values(array_filter($following, fn (Event $event): bool => !$this->endsBefore($event->period()->start())));

        return null === $this->events ? $kept : \array_slice($kept, 0, $this->events - 1);
    }
}
