<?php

declare(strict_types=1);

namespace App\Application\Stock;

use App\Domain\Shared\Money;
use App\Domain\Stock\StockCheck;
use App\Domain\Stock\StockCheckRepository;
use Symfony\Component\Uid\Ulid;

final readonly class ConsumedSupplies
{
    public function __construct(private StockCheckRepository $checks)
    {
    }

    /**
     * @return array<string, Money>
     */
    public function byEvent(): array
    {
        $consumed = [];
        foreach ($this->checks->consumingSupplies() as $check) {
            $event = (string) $check->event()->id();
            $consumed[$event] = ($consumed[$event] ?? Money::zero())->add($check->consumedSupplies());
        }

        return $consumed;
    }

    public function atEvent(Ulid $eventId): Money
    {
        return Money::sum(array_map(static fn (StockCheck $check): Money => $check->consumedSupplies(), $this->checks->ofEvent($eventId)));
    }
}
