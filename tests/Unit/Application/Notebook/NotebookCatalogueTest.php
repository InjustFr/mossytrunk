<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Notebook;

use App\Application\Notebook\NotebookCatalogue;
use App\Domain\Product\Product;
use App\Domain\Product\ProductType;
use App\Domain\Shared\Money;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;

final class NotebookCatalogueTest extends TestCase
{
    private ProductType $prints;
    private Product $dragon;
    private NotebookCatalogue $catalogue;

    protected function setUp(): void
    {
        $this->prints = ProductType::create(TestWorkspace::get(), 'Tirage', 'TIR');
        $this->prints->prefixNames(false);
        $this->dragon = Product::create(TestWorkspace::get(), 'TIR-1', 'Dragon', Money::cents(1_500), $this->prints, ['A4', 'A3']);
        $this->catalogue = NotebookCatalogue::of([$this->dragon]);
    }

    public function testTheCatalogueListsProductsByType(): void
    {
        self::assertSame(
            [['id' => (string) $this->prints->id(), 'name' => 'Tirage', 'products' => [['id' => (string) $this->dragon->id(), 'name' => 'Dragon', 'variants' => ['A4', 'A3']]]]],
            $this->catalogue->types(),
        );
    }

    public function testAProductLineTakesTheProductTypeAndItsVariantSpelling(): void
    {
        $line = $this->catalogue->line('dragon a4', 2, (string) $this->dragon->id(), 'a4', null);

        self::assertTrue($this->dragon->id()->equals($line->productId));
        self::assertTrue($this->prints->id()->equals($line->typeId));
        self::assertSame('A4', $line->variant);
        self::assertSame('Dragon — A4', $line->label);
    }

    public function testAnUnknownVariantIsDropped(): void
    {
        $line = $this->catalogue->line('dragon A5', 1, (string) $this->dragon->id(), 'A5', null);

        self::assertNull($line->variant);
        self::assertSame('Dragon', $line->label);
    }

    public function testAnUnknownProductFallsBackToItsTypeThenToWhatIsWritten(): void
    {
        $typed = $this->catalogue->line('tirage', 1, 'nope', null, (string) $this->prints->id());
        self::assertNull($typed->productId);
        self::assertSame('Tirage', $typed->label);

        $unknown = $this->catalogue->line('truc vert', 1, null, null, 'nope');
        self::assertNull($unknown->typeId);
        self::assertSame('truc vert', $unknown->label);
    }
}
