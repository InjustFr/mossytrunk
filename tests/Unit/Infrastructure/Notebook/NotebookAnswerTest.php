<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Notebook;

use App\Application\Notebook\NotebookCatalogue;
use App\Infrastructure\Notebook\NotebookAnswer;
use PHPUnit\Framework\TestCase;

final class NotebookAnswerTest extends TestCase
{
    public function testTheAnswerBecomesNotebookEntriesWithoutEmptySales(): void
    {
        $entries = NotebookAnswer::entries([
            'entries' => [
                ['page' => 2, 'lines' => [['written' => ' 2 stickers ', 'quantity' => 2, 'productId' => null, 'variant' => '', 'typeId' => null]]],
                ['page' => 2, 'lines' => []],
                ['page' => 0, 'lines' => [['written' => 'badge', 'quantity' => 0, 'productId' => null, 'variant' => null, 'typeId' => null]]],
            ],
        ], NotebookCatalogue::of([]));

        self::assertCount(2, $entries);
        self::assertSame(2, $entries[0]->page);
        self::assertSame('2 stickers', $entries[0]->lines[0]->written);
        self::assertSame(2, $entries[0]->lines[0]->quantity);
        self::assertNull($entries[0]->lines[0]->variant);
        self::assertSame(1, $entries[1]->page, 'pages start at 1');
        self::assertSame(1, $entries[1]->lines[0]->quantity, 'an item is sold at least once');
    }
}
