<?php

declare(strict_types=1);

namespace App\Fixtures\Factory;

use App\Domain\Event\Event;
use App\Domain\Shared\DateRange;
use App\Domain\Shared\Money;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * Builds events through Event::schedule(). Pass `expenses` as [label => cents].
 *
 * @extends PersistentObjectFactory<Event>
 */
final class EventFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Event::class;
    }

    /**
     * @param array<string, int> $expenses
     */
    public function withExpenses(array $expenses): static
    {
        return $this->afterInstantiate(static function (Event $event) use ($expenses): void {
            foreach ($expenses as $label => $cents) {
                $event->addExpense($label, Money::cents($cents));
            }
        });
    }

    public static function during(string $start, string $end): DateRange
    {
        return DateRange::fromDates(new \DateTimeImmutable($start), new \DateTimeImmutable($end));
    }

    protected function defaults(): array
    {
        $start = \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-1 year', '+3 months'));

        return [
            'name' => 'Convention '.self::faker()->city(),
            'location' => self::faker()->city(),
            'period' => DateRange::fromDates($start, $start->modify('+1 day')),
        ];
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('schedule')->disableHydration());
    }
}
