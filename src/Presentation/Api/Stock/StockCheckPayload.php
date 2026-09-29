<?php

declare(strict_types=1);

namespace App\Presentation\Api\Stock;

use App\Application\Stock\TakeStockCheck\CountedItem;
use App\Application\Stock\TakeStockCheck\TakeStockCheck;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class StockCheckPayload
{
    /**
     * @param list<CountedItemPayload> $items
     */
    public function __construct(
        #[Assert\Count(min: 1, minMessage: 'Comptez au moins un article.')]
        #[Assert\Valid]
        public array $items = [],
    ) {
    }

    public function toCommand(string $eventId): TakeStockCheck
    {
        return new TakeStockCheck($eventId, array_map(static fn (CountedItemPayload $item): CountedItem => $item->toCountedItem(), $this->items));
    }
}
