<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Notebook;

use App\Application\Notebook\NotebookCatalogue;
use App\Domain\Notebook\NotebookLine;
use App\Domain\Notebook\WrittenItem;
use App\Domain\Product\Product;
use App\Domain\Product\ProductType;
use App\Domain\Shared\Money;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;

final class NotebookCatalogueTest extends TestCase
{
    private ProductType $notebooks;
    private ProductType $prints;
    private Product $lichen;
    private Product $fern;
    private Product $dragon;
    private NotebookCatalogue $catalogue;

    protected function setUp(): void
    {
        $this->notebooks = ProductType::create(TestWorkspace::get(), 'Carnet', 'CAR');
        $this->notebooks->prefixNames(false);
        $this->prints = ProductType::create(TestWorkspace::get(), 'Tirage', 'TIR');
        $this->lichen = Product::create(TestWorkspace::get(), 'CAR-1', 'Carnet Lichen', Money::cents(1_200), $this->notebooks);
        $this->fern = Product::create(TestWorkspace::get(), 'CAR-2', 'Carnet Fougère', Money::cents(1_200), $this->notebooks);
        $this->dragon = Product::create(TestWorkspace::get(), 'TIR-1', 'Dragon', Money::cents(1_500), $this->prints, ['A4', 'A3']);
        $this->catalogue = NotebookCatalogue::of([$this->lichen, $this->fern, $this->dragon]);
    }

    public function testTheDistinctiveWordNamesTheProduct(): void
    {
        $line = $this->line('lichen', 2);

        self::assertTrue($this->lichen->id()->equals($line->productId));
        self::assertTrue($this->notebooks->id()->equals($line->typeId));
        self::assertSame('Carnet Lichen', $line->label);
        self::assertSame(2, $line->quantity);
        self::assertSame('lichen', $line->written);
    }

    public function testSmallReadingMistakesAndAccentsAreTolerated(): void
    {
        self::assertTrue($this->fern->id()->equals($this->line('fougere')->productId));
        self::assertTrue($this->lichen->id()->equals($this->line('lichcn')->productId));
    }

    public function testAVariantWrittenWithTheProductIsKept(): void
    {
        $line = $this->line('tirage dragon a4');

        self::assertTrue($this->dragon->id()->equals($line->productId));
        self::assertSame('A4', $line->variant);
        self::assertSame('Tirage Dragon — A4', $line->label);
    }

    public function testAWordSharedBySeveralProductsOnlyNamesTheirType(): void
    {
        $line = $this->line('carnet');

        self::assertNull($line->productId);
        self::assertTrue($this->notebooks->id()->equals($line->typeId));
        self::assertSame('Carnet', $line->label);
    }

    public function testAnUnknownItemStaysAsWritten(): void
    {
        $line = $this->line('truc vert');

        self::assertNull($line->productId);
        self::assertNull($line->typeId);
        self::assertSame('truc vert', $line->label);
    }

    private function line(string $written, int $quantity = 1): NotebookLine
    {
        return $this->catalogue->line(new WrittenItem($written, $quantity), $written);
    }
}
