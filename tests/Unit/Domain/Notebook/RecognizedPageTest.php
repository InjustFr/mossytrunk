<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Notebook;

use App\Domain\Notebook\RecognizedLine;
use App\Domain\Notebook\RecognizedPage;
use PHPUnit\Framework\TestCase;

final class RecognizedPageTest extends TestCase
{
    public function testAWideGapBetweenLinesBecomesABlankLine(): void
    {
        $page = new RecognizedPage([
            new RecognizedLine('2 lichen', 100),
            new RecognizedLine('fougère', 150),
            new RecognizedLine('badge', 260),
            new RecognizedLine('sticker', 310),
        ]);

        self::assertSame("2 lichen\nfougère\n\nbadge\nsticker", $page->text());
    }

    public function testEvenlySpacedLinesStayTogether(): void
    {
        self::assertSame("a\nb", new RecognizedPage([new RecognizedLine('a', 10), new RecognizedLine('b', 60)])->text());
        self::assertSame('', new RecognizedPage([])->text());
    }
}
