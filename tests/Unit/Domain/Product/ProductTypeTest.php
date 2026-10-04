<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Product;

use App\Domain\Product\Exception\DuplicateTypeVariant;
use App\Domain\Product\Exception\EmptyVariant;
use App\Domain\Product\Exception\InvalidProduct;
use App\Domain\Product\Exception\UnknownTypeVariant;
use App\Domain\Product\Product;
use App\Domain\Product\ProductType;
use App\Domain\Shared\Money;
use App\Tests\Support\DomainExceptions;
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

        $print->prefixNames(false);
        self::assertSame('Forêt', $product->displayName());
    }

    public function testVariantsAreEmptyAndNamesPrefixedByDefault(): void
    {
        $print = ProductType::create(TestWorkspace::get(), 'Print', 'PRI');

        self::assertSame([], $print->variants());
        self::assertTrue($print->prefixesNames());
        self::assertSame('Print Forêt', $print->nameProduct('Forêt'));

        $print->prefixNames(false);
        self::assertFalse($print->prefixesNames());
        self::assertSame('Forêt', $print->nameProduct('Forêt'));
    }

    public function testAnOfferedVariantIsReusedCaseInsensitivelyWithTheTypeSpelling(): void
    {
        $print = ProductType::create(TestWorkspace::get(), 'Print', 'PRI');

        self::assertSame('A3', $print->offerVariant(' A3 '));
        self::assertSame('A3', $print->offerVariant('a3'));
        self::assertSame(['A3', 'A4'], $print->offerVariants(['a3', 'A4', ' ', 'a4']));
        self::assertSame(['A3', 'A4'], $print->variants());
    }

    public function testAnEmptyVariantIsNotOffered(): void
    {
        $this->expectException(EmptyVariant::class);

        ProductType::create(TestWorkspace::get(), 'Print', 'PRI')->offerVariant('  ');
    }

    public function testDefinedVariantsReplaceTheListInTheirOrder(): void
    {
        $print = ProductType::create(TestWorkspace::get(), 'Print', 'PRI');
        $print->offerVariants(['A3', 'A4']);

        $print->defineVariants(['A5', ' A4 ', 'A3']);

        self::assertSame(['A5', 'A4', 'A3'], $print->variants());
    }

    public function testDefinedVariantsAreUnique(): void
    {
        $print = ProductType::create(TestWorkspace::get(), 'Print', 'PRI');
        $print->defineVariants(['A3', 'A4']);

        DomainExceptions::assertThrown(new DuplicateTypeVariant('a4'), static fn () => $print->defineVariants(['A4', 'a4']));

        self::assertSame(['A3', 'A4'], $print->variants());
    }

    public function testDefinedVariantsAreNotEmpty(): void
    {
        $print = ProductType::create(TestWorkspace::get(), 'Print', 'PRI');

        DomainExceptions::assertThrown(new EmptyVariant(), static fn () => $print->defineVariants(['A4', '  ']));

        self::assertSame([], $print->variants());
    }

    public function testRenamingAVariantKeepsItsPlaceAndReturnsTheFormerSpelling(): void
    {
        $print = ProductType::create(TestWorkspace::get(), 'Print', 'PRI');
        $print->defineVariants(['A4', 'A3']);

        self::assertSame('A4', $print->renameVariant('a4', ' A4 portrait '));
        self::assertSame(['A4 portrait', 'A3'], $print->variants());

        self::assertSame('A3', $print->renameVariant('A3', 'a3'));
        self::assertSame(['A4 portrait', 'a3'], $print->variants());
    }

    public function testRenamingAnUnknownVariantIsRefused(): void
    {
        $print = ProductType::create(TestWorkspace::get(), 'Print', 'PRI');
        $print->defineVariants(['A4']);

        DomainExceptions::assertThrown(new UnknownTypeVariant('Print', 'A5'), static fn () => $print->renameVariant('A5', 'A6'));
    }

    public function testRenamingOntoAnotherVariantIsRefused(): void
    {
        $print = ProductType::create(TestWorkspace::get(), 'Print', 'PRI');
        $print->defineVariants(['A4', 'A3']);

        DomainExceptions::assertThrown(new DuplicateTypeVariant('a3'), static fn () => $print->renameVariant('A4', ' a3 '));

        self::assertSame(['A4', 'A3'], $print->variants());
    }

    public function testVariantsCanBeArchivedAndStayArchivedWhenRenamed(): void
    {
        $print = ProductType::create(TestWorkspace::get(), 'Print', 'PRI');
        $print->defineVariants(['A5', 'A4', 'A3']);

        $print->archiveVariants([' a5 ', 'A5']);
        $print->renameVariant('A5', 'Petit');

        self::assertSame(['Petit'], $print->archivedVariants());
        self::assertSame(['A4', 'A3'], $print->activeVariants());
        self::assertTrue($print->isVariantArchived('petit'));
    }

    public function testOnlyItsOwnVariantsCanBeArchivedAndRemovedOnesAreForgotten(): void
    {
        $print = ProductType::create(TestWorkspace::get(), 'Print', 'PRI');
        $print->defineVariants(['A5', 'A4']);
        $print->archiveVariants(['A5']);

        $print->defineVariants(['A4']);
        self::assertSame([], $print->archivedVariants());

        $this->expectException(UnknownTypeVariant::class);
        $print->archiveVariants(['A0']);
    }

    public function testATypeIsArchivedWithItsProductsAndRestored(): void
    {
        $print = ProductType::create(TestWorkspace::get(), 'Print', 'PRI');
        $product = Product::create(TestWorkspace::get(), 'PRI-FOR', 'Forêt', Money::cents(1_500), $print);

        $print->archive(new \DateTimeImmutable('2026-09-30'));
        self::assertTrue($print->isArchived());
        self::assertTrue($product->isArchived());
        self::assertFalse($product->isArchivedItself());

        $print->restore();
        self::assertFalse($product->isArchived());
    }
}
