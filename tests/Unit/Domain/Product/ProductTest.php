<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Product;

use App\Domain\Product\InvalidProduct;
use App\Domain\Product\Product;
use App\Domain\Shared\InvalidMoney;
use App\Domain\Shared\Money;
use App\Tests\Support\Costs;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public function testBuyingPriceDefaultsToZero(): void
    {
        $product = Product::create(TestWorkspace::get(), 'STK-01', 'Sticker', Money::cents(400));

        self::assertTrue($product->buyingPrice()->isZero());
        self::assertSame(400, $product->sellingPrice()->amount());
        self::assertFalse($product->hasVariants());
    }

    public function testNameAndReferenceAreTrimmedAndRequired(): void
    {
        $product = Product::create(TestWorkspace::get(), '  STK-01 ', ' Sticker ', Money::cents(400));
        self::assertSame('STK-01', $product->reference());
        self::assertSame('Sticker', $product->name());

        $this->expectException(InvalidProduct::class);
        Product::create(TestWorkspace::get(), 'STK-01', '   ', Money::cents(400));
    }

    public function testReferenceIsRequired(): void
    {
        $this->expectException(InvalidProduct::class);

        Product::create(TestWorkspace::get(), '', 'Sticker', Money::cents(400));
    }

    public function testPricesCannotBeNegative(): void
    {
        $product = Product::create(TestWorkspace::get(), 'STK-01', 'Sticker', Money::cents(400));

        $this->expectException(InvalidMoney::class);
        $product->reprice(Money::cents(-1));
    }

    public function testVariantsAreUniqueAndNotEmpty(): void
    {
        $product = Product::create(TestWorkspace::get(), 'TS-01', 'T-shirt', Money::cents(2_000), variants: ['S', 'M']);

        try {
            $product->addVariant('M');
            self::fail('Duplicate variant accepted');
        } catch (InvalidProduct) {
        }

        $this->expectException(InvalidProduct::class);
        $product->addVariant('  ');
    }

    public function testReplaceVariantsIsAtomic(): void
    {
        $product = Product::create(TestWorkspace::get(), 'TS-01', 'T-shirt', Money::cents(2_000), variants: ['S', 'M']);

        try {
            $product->replaceVariants(['L', 'L']);
            self::fail('Duplicate variant accepted');
        } catch (InvalidProduct) {
        }

        self::assertSame(['S', 'M'], $product->variants());

        $product->replaceVariants(['M', 'XL']);
        self::assertSame(['M', 'XL'], $product->variants());
    }

    public function testProductWithVariantsRequiresOneOfThem(): void
    {
        $product = Costs::bought(Product::create(TestWorkspace::get(), 'TS-01', 'T-shirt', Money::cents(2_000), ['Mousse', 'Fougère']), 800);

        $item = $product->sellable('Mousse');
        self::assertTrue($item->productId->equals($product->id()));
        self::assertSame('Mousse', $item->variant);
        self::assertSame(2_000, $item->sellingPrice->amount());
        self::assertSame(800, $item->buyingPrice->amount());
        self::assertSame('T-shirt — Mousse', $item->label());

        $this->expectExceptionMessage('Choisissez une variante pour « T-shirt ».');
        $product->sellable(null);
    }

    public function testUnknownVariantIsRejected(): void
    {
        $product = Product::create(TestWorkspace::get(), 'TS-01', 'T-shirt', Money::cents(2_000), variants: ['Mousse']);

        $this->expectException(InvalidProduct::class);
        $product->sellable('Lichen');
    }

    public function testUniqueProductAcceptsNoVariant(): void
    {
        $product = Product::create(TestWorkspace::get(), 'ART-01', 'Original painting', Money::cents(15_000));

        self::assertNull($product->sellable(null)->variant);
        self::assertNull($product->sellable('  ')->variant);
        self::assertSame('Original painting', $product->sellable(null)->label());

        $this->expectException(InvalidProduct::class);
        $product->sellable('Blue');
    }
}
