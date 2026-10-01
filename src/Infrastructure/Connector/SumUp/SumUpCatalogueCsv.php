<?php

declare(strict_types=1);

namespace App\Infrastructure\Connector\SumUp;

use App\Application\Integration\CatalogueItem;
use App\Domain\Shared\Money;

final readonly class SumUpCatalogueCsv
{
    private const array COLUMNS = ['Item name', 'Variations', 'Price', 'Tax rate (%)', 'Track inventory?', 'Quantity', 'SKU', 'Description', 'Category'];
    private const string NO_INVENTORY = 'No';

    /**
     * @param list<CatalogueItem> $items
     */
    public function of(array $items): string
    {
        $rows = [self::COLUMNS];
        foreach ($items as $item) {
            array_push($rows, ...$this->rowsOf($item));
        }

        return implode('', array_map($this->line(...), $rows));
    }

    /**
     * @return list<list<string>>
     */
    private function rowsOf(CatalogueItem $item): array
    {
        if ([] === $item->variants) {
            return [[$item->name, '', self::price($item->price), '', self::NO_INVENTORY, '', $item->reference, '', $item->category]];
        }

        return [
            [$item->name, '', '', '', '', '', '', '', $item->category],
            ...array_map(static fn (string $variant): array => ['', $variant, self::price($item->price), '', self::NO_INVENTORY, '', $item->reference.'-'.$variant, '', ''], $item->variants),
        ];
    }

    /**
     * @param list<string> $cells
     */
    private function line(array $cells): string
    {
        return implode(',', array_map(static fn (string $cell): string => 1 === preg_match('/[",\r\n]/', $cell) ? '"'.str_replace('"', '""', $cell).'"' : $cell, $cells))."\r\n";
    }

    private static function price(Money $price): string
    {
        return number_format($price->amount() / 100, 2, '.', '');
    }
}
