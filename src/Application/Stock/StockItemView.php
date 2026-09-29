<?php

declare(strict_types=1);

namespace App\Application\Stock;

use App\Domain\Stock\StockItem;
use App\Domain\Stock\StockLot;

final readonly class StockItemView
{
    /**
     * @param list<array{id: string, receivedAt: string, origin: string, sourceId: ?string, quantity: int, remaining: int, totalCost: int, unitCost: int}> $lots
     */
    public function __construct(
        public ?string $variant,
        public int $onHand,
        public bool $low,
        public bool $negative,
        public int $remainingValue,
        public array $lots,
    ) {
    }

    public static function of(?string $variant, ?StockItem $item, int $threshold): self
    {
        $onHand = $item?->onHand() ?? 0;

        return new self(
            $variant,
            $onHand,
            $onHand <= $threshold,
            $onHand < 0,
            $item?->remainingValue()->amount() ?? 0,
            array_map(static fn (StockLot $lot): array => [
                'id' => (string) $lot->id(),
                'receivedAt' => $lot->receivedAt()->format(\DateTimeInterface::ATOM),
                'origin' => $lot->origin()->value,
                'sourceId' => null === $lot->sourceId() ? null : (string) $lot->sourceId(),
                'quantity' => $lot->quantity(),
                'remaining' => $lot->remaining(),
                'totalCost' => $lot->totalCost()->amount(),
                'unitCost' => $lot->unitCost()->amount(),
            ], array_reverse($item?->lots() ?? [])),
        );
    }
}
