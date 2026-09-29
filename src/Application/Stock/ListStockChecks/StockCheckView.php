<?php

declare(strict_types=1);

namespace App\Application\Stock\ListStockChecks;

use App\Domain\Product\Product;
use App\Domain\Stock\StockCheck;
use App\Domain\Stock\StockCheckLine;

final readonly class StockCheckView
{
    /**
     * @param list<array{id: string, label: string, expected: int, counted: int, missing: int, surplus: int, lossCost: int, unexplained: int, dismissed: bool, missedSales: int}> $lines
     */
    public function __construct(
        public string $id,
        public string $checkedAt,
        public int $unexplainedUnits,
        public int $missedSales,
        public array $lines,
    ) {
    }

    /**
     * @param array<string, Product> $products
     */
    public static function of(StockCheck $check, array $products): self
    {
        $lines = array_map(static fn (StockCheckLine $line): array => [
            'id' => (string) $line->id(),
            'label' => $line->label(),
            'expected' => $line->expected(),
            'counted' => $line->counted(),
            'missing' => $line->missing(),
            'surplus' => $line->surplus(),
            'lossCost' => $line->lossCost()->amount(),
            'unexplained' => $line->unexplained(),
            'dismissed' => $line->isDismissed(),
            'missedSales' => ($products[(string) $line->productId()] ?? null)?->sellingPrice()->multiply($line->unexplained())->amount() ?? 0,
        ], $check->lines());

        return new self(
            (string) $check->id(),
            $check->checkedAt()->format(\DateTimeInterface::ATOM),
            $check->unexplainedUnits(),
            array_sum(array_column($lines, 'missedSales')),
            $lines,
        );
    }
}
