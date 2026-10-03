<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Notebook;

use App\Domain\Notebook\Abbreviation;
use App\Domain\Notebook\Exception\InvalidNotebook;
use App\Domain\Notebook\NotebookTemplate;
use App\Domain\Notebook\SaleSeparation;
use App\Domain\Notebook\WrittenItem;
use App\Domain\Notebook\WrittenSale;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;

final class NotebookTemplateTest extends TestCase
{
    public function testNumberedSalesStartWithTheirNumberAndGoOnOnTheNextLines(): void
    {
        $template = NotebookTemplate::standard(TestWorkspace::get());

        self::assertEquals(
            [
                new WrittenSale(1, [new WrittenItem('lichen', 2)]),
                new WrittenSale(1, [new WrittenItem('fougère', 1), new WrittenItem('sticker renard', 3)]),
                new WrittenSale(2, [new WrittenItem('badge', 1)]),
            ],
            $template->sales([
                "Japan Expo samedi\n1) 2 lichen\n2 - fougère 12€ CB\n3 sticker renard",
                "Dimanche\n#14. badge",
            ]),
        );
    }

    public function testOneSalePerLine(): void
    {
        $template = NotebookTemplate::standard(TestWorkspace::get());
        $template->configure(SaleSeparation::OnePerLine, []);

        self::assertEquals(
            [new WrittenSale(1, [new WrittenItem('lichen', 2)]), new WrittenSale(1, [new WrittenItem('fougère', 1), new WrittenItem('badge', 1)])],
            $template->sales(["1) 2 lichen\n\nfougère + badge"]),
        );
    }

    public function testSalesSeparatedByAGapOrARule(): void
    {
        $template = NotebookTemplate::standard(TestWorkspace::get());
        $template->configure(SaleSeparation::Gap, []);

        self::assertEquals(
            [
                new WrittenSale(1, [new WrittenItem('lichen', 2), new WrittenItem('fougère', 1)]),
                new WrittenSale(1, [new WrittenItem('badge', 1)]),
                new WrittenSale(1, [new WrittenItem('sticker', 4)]),
            ],
            $template->sales(["2 lichen\nfougère\n\nbadge\n-----\nsticker x4"]),
        );
    }

    public function testAbbreviationsAreExpandedAsWholeWords(): void
    {
        $template = NotebookTemplate::standard(TestWorkspace::get());
        $template->configure(SaleSeparation::Numbered, [new Abbreviation('stk', 'sticker'), new Abbreviation('L', 'lichen')]);

        self::assertSame('sticker lichen', $template->expand('STK L'));
        self::assertSame('stkx Lune', $template->expand('stkx Lune'));
    }

    public function testAnAbbreviationIsDefinedOnce(): void
    {
        $this->expectException(InvalidNotebook::class);

        NotebookTemplate::standard(TestWorkspace::get())->configure(SaleSeparation::Numbered, [new Abbreviation('stk', 'sticker'), new Abbreviation('STK', 'stickers')]);
    }

    public function testAnAbbreviationAndItsMeaningAreRequired(): void
    {
        $this->expectException(InvalidNotebook::class);

        new Abbreviation(' ', 'sticker');
    }
}
