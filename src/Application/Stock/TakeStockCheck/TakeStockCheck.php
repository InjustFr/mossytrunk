<?php

declare(strict_types=1);

namespace App\Application\Stock\TakeStockCheck;

final readonly class TakeStockCheck
{
    /**
     * @param list<CountedItem> $items
     */
    public function __construct(
        public string $eventId,
        public array $items,
    ) {
    }
}
