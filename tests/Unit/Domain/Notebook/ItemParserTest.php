<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Notebook;

use App\Domain\Notebook\ItemParser;
use App\Domain\Notebook\WrittenItem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ItemParserTest extends TestCase
{
    /**
     * @param list<WrittenItem> $expected
     */
    #[DataProvider('writings')]
    public function testItemsAndQuantitiesAreReadWithoutPricesNorPayments(string $written, array $expected): void
    {
        self::assertEquals($expected, ItemParser::items([$written]));
    }

    /**
     * @return iterable<string, array{string, list<WrittenItem>}>
     */
    public static function writings(): iterable
    {
        yield 'quantity first' => ['2 lichen', [new WrittenItem('lichen', 2)]];
        yield 'quantity with x' => ['2x lichen', [new WrittenItem('lichen', 2)]];
        yield 'x before quantity' => ['x2 lichen', [new WrittenItem('lichen', 2)]];
        yield 'quantity last' => ['lichen x 3', [new WrittenItem('lichen', 3)]];
        yield 'no quantity' => ['print dragon A4', [new WrittenItem('print dragon A4', 1)]];
        yield 'price and payment' => ['fougère 12,50€ CB', [new WrittenItem('fougère', 1)]];
        yield 'bare price' => ['fougère 12', [new WrittenItem('fougère', 1)]];
        yield 'several items' => ['2 lichen, fougère + 3 badges ; total 30€', [new WrittenItem('lichen', 2), new WrittenItem('fougère', 1), new WrittenItem('badges', 3)]];
        yield 'nothing sold' => ['= 24€ espèces', []];
    }
}
