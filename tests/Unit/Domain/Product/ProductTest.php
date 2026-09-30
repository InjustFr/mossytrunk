<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Product;

use App\Domain\Product\Exception\InvalidProduct;
use App\Domain\Product\Exception\VariantRequired;
use App\Domain\Product\Product;
use App\Domain\Shared\Exception\InvalidMoney;
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

    public function testReferenceCanBeChanged(): void
    {
        $product = Product::create(TestWorkspace::get(), 'STK-01', 'Sticker', Money::cents(400));

        $product->changeReference(' STK-MOUSSE ');

        self::assertSame('STK-MOUSSE', $product->reference());
    }

    public function testChangedReferenceIsRequiredAndShort(): void
    {
        $product = Product::create(TestWorkspace::get(), 'STK-01', 'Sticker', Money::cents(400));

        try {
            $product->changeReference('  ');
            self::fail('a blank reference is refused');
        } catch (InvalidProduct) {
        }

        $this->expectException(InvalidProduct::class);
        $product->changeReference(str_repeat('A', Product::REFERENCE_MAX_LENGTH + 1));
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

        $this->expectExceptionObject(new VariantRequired('T-shirt'));
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

    public function testSellingPriceChangesAreKeptInTheHistory(): void
    {
        $product = Product::create(TestWorkspace::get(), 'STK-01', 'Sticker', Money::cents(400));

        $product->reprice(Money::cents(400));
        $product->reprice(Money::cents(450));

        self::assertSame([400, 450], array_map(static fn ($change): int => $change->price()->amount(), $product->priceHistory()));
    }

    public function testBuyingPriceStartsUnknownAndFollowsPurchases(): void
    {
        $product = Product::create(TestWorkspace::get(), 'STK-01', 'Sticker', Money::cents(400));
        self::assertTrue($product->buyingPrice()->isZero());

        $product->bought(Money::cents(90));

        self::assertSame(90, $product->buyingPrice()->amount());
    }

    public function testPastPricesCanBeRecordedAndTheLatestIsTheSellingPrice(): void
    {
        $product = Product::create(TestWorkspace::get(), 'STK-01', 'Sticker', Money::cents(500));
        $now = new \DateTimeImmutable('2026-09-29 12:00');

        $product->recordPrice(Money::cents(400), new \DateTimeImmutable('2026-07-01'), $now);

        self::assertSame(500, $product->sellingPrice()->amount());
        self::assertSame([400, 500], array_map(static fn ($change): int => $change->price()->amount(), $product->priceHistory()));
    }

    public function testAmendingTheLatestPriceCorrectsTheSellingPriceWithoutANewEntry(): void
    {
        $product = Product::create(TestWorkspace::get(), 'STK-01', 'Sticker', Money::cents(9_999));
        $now = new \DateTimeImmutable('2026-09-29 12:00');
        $current = $product->priceHistory()[0];

        $product->amendPrice($current->id(), Money::cents(400), new \DateTimeImmutable('2026-09-01'), $now);

        self::assertSame(400, $product->sellingPrice()->amount());
        self::assertCount(1, $product->priceHistory());
        self::assertEquals(new \DateTimeImmutable('2026-09-01'), $product->priceHistory()[0]->since());
    }

    public function testForgettingTheLatestPriceFallsBackToThePreviousOne(): void
    {
        $product = Product::create(TestWorkspace::get(), 'STK-01', 'Sticker', Money::cents(400));
        $product->reprice(Money::cents(9_999));

        $product->forgetPrice($product->priceHistory()[1]->id());

        self::assertSame(400, $product->sellingPrice()->amount());
        $this->expectException(InvalidProduct::class);
        $product->forgetPrice($product->priceHistory()[0]->id());
    }

    public function testAPriceCannotBeDatedInTheFuture(): void
    {
        $product = Product::create(TestWorkspace::get(), 'STK-01', 'Sticker', Money::cents(400));

        $this->expectException(InvalidProduct::class);

        $product->recordPrice(Money::cents(500), new \DateTimeImmutable('2026-10-01'), new \DateTimeImmutable('2026-09-29'));
    }
}
