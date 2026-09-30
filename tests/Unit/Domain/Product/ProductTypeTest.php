<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Product;

use App\Domain\Product\Exception\InvalidProduct;
use App\Domain\Product\Product;
use App\Domain\Product\ProductType;
use App\Domain\Shared\Money;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductTypeTest extends TestCase
{
    /**
     * @return iterable<array{string, string}>
     */
    public static function codes(): iterable
    {
        yield ['Print', 'PRI'];
        yield ['T-shirt', 'TSH'];
        yield ['Épingle', 'EPI'];
        yield ['A', 'A'];
        yield ['!!', 'TYP'];
    }

    #[DataProvider('codes')]
    public function testCodeIsDerivedFromName(string $name, string $code): void
    {
        self::assertSame($code, ProductType::codeFor($name));
    }

    public function testNameIsRequired(): void
    {
        $this->expectException(InvalidProduct::class);

        ProductType::create(TestWorkspace::get(), '  ', 'PRI');
    }

    public function testCodeFormat(): void
    {
        $this->expectException(InvalidProduct::class);

        ProductType::create(TestWorkspace::get(), 'Print', 'pri-1');
    }

    public function testCodeCanBeChangedAndIsUppercased(): void
    {
        $print = ProductType::create(TestWorkspace::get(), 'Print', 'PRI');

        $print->recode(' aff2 ');

        self::assertSame('AFF2', $print->code());
    }

    public function testChangedCodeKeepsItsFormat(): void
    {
        $print = ProductType::create(TestWorkspace::get(), 'Print', 'PRI');

        $this->expectException(InvalidProduct::class);
        $print->recode('AFFICHES1');
    }

    public function testColorIsNormalized(): void
    {
        $print = ProductType::create(TestWorkspace::get(), 'Print', 'PRI', ' #4F6D8F ');
        self::assertSame('#4f6d8f', $print->color());

        $print->recolor('#C29A2E');
        self::assertSame('#c29a2e', $print->color());
    }

    /**
     * @return iterable<array{string}>
     */
    public static function invalidColors(): iterable
    {
        yield [''];
        yield ['red'];
        yield ['#abc'];
        yield ['#12345g'];
    }

    #[DataProvider('invalidColors')]
    public function testColorMustBeHex(string $color): void
    {
        $this->expectException(InvalidProduct::class);

        ProductType::create(TestWorkspace::get(), 'Print', 'PRI')->recolor($color);
    }

    public function testPaletteColorsCycle(): void
    {
        self::assertSame(ProductType::PALETTE[0], ProductType::paletteColor(0));
        self::assertSame(ProductType::PALETTE[1], ProductType::paletteColor(\count(ProductType::PALETTE) + 1));
    }

    public function testTypedProductIsDisplayedAsTypeThenName(): void
    {
        $print = ProductType::create(TestWorkspace::get(), 'Print', 'PRI');
        $product = Product::create(TestWorkspace::get(), 'PRI-FORET', 'Forêt', Money::cents(1_500), variants: ['A4'], type: $print);

        self::assertSame('Print Forêt', $product->displayName());
        self::assertSame('Print Forêt', $product->sellable('A4')->productName);
        self::assertSame('Print Forêt — A4', $product->sellable('A4')->label());

        $print->rename('Affiche');
        self::assertSame('Affiche Forêt', $product->displayName());

        $product->classify(null);
        self::assertSame('Forêt', $product->displayName());
    }
}
