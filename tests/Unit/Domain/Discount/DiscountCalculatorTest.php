<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Discount;

use App\Domain\Discount\AppliedDiscount;
use App\Domain\Discount\BasketLine;
use App\Domain\Discount\DiscountCalculator;
use App\Domain\Discount\DiscountRule;
use App\Domain\Product\Product;
use App\Domain\Shared\Money;
use PHPUnit\Framework\TestCase;

final class DiscountCalculatorTest extends TestCase
{
    private Product $sticker;
    private Product $bigSticker;
    private Product $print;
    private DiscountCalculator $calculator;

    protected function setUp(): void
    {
        $this->sticker = Product::create('STK', 'Sticker', Money::cents(400));
        $this->bigSticker = Product::create('STK-XL', 'Sticker XL', Money::cents(600));
        $this->print = Product::create('PRT', 'Print', Money::cents(1_500), variants: ['A4', 'A3']);
        $this->calculator = new DiscountCalculator();
    }

    public function testNoDiscountBelowBundleSize(): void
    {
        $rule = DiscountRule::create('3 stickers pour 10 €', [$this->sticker], 3, Money::cents(1_000));

        self::assertSame([], $this->calculator->calculate([$this->line($this->sticker, 2)], [$rule]));
    }

    public function testSingleBundle(): void
    {
        $rule = DiscountRule::create('3 stickers pour 10 €', [$this->sticker], 3, Money::cents(1_000));

        $discounts = $this->calculator->calculate([$this->line($this->sticker, 4)], [$rule]);

        self::assertEquals([new AppliedDiscount('3 stickers pour 10 €', Money::cents(200))], $discounts);
    }

    public function testSeveralBundlesOfTheSameRuleAreGrouped(): void
    {
        $rule = DiscountRule::create('3 stickers pour 10 €', [$this->sticker], 3, Money::cents(1_000));

        $discounts = $this->calculator->calculate([$this->line($this->sticker, 7)], [$rule]);

        self::assertEquals([new AppliedDiscount('3 stickers pour 10 € ×2', Money::cents(400))], $discounts);
    }

    public function testEligibleProductsCanBeMixedAndMostExpensiveUnitsAreBundledFirst(): void
    {
        $rule = DiscountRule::create('3 stickers pour 10 €', [$this->sticker, $this->bigSticker], 3, Money::cents(1_000));

        // units: 6, 6, 4, 4 → bundle 6+6+4 = 16 € for 10 € → saving 6 €
        $discounts = $this->calculator->calculate([$this->line($this->sticker, 2), $this->line($this->bigSticker, 2)], [$rule]);

        self::assertEquals([new AppliedDiscount('3 stickers pour 10 €', Money::cents(600))], $discounts);
    }

    public function testIneligibleProductsAreIgnored(): void
    {
        $rule = DiscountRule::create('3 stickers pour 10 €', [$this->sticker], 3, Money::cents(1_000));

        self::assertSame([], $this->calculator->calculate([$this->line($this->sticker, 2), $this->line($this->print, 5)], [$rule]));
    }

    public function testBundleWithoutSavingIsNotApplied(): void
    {
        $rule = DiscountRule::create('2 stickers pour 9 €', [$this->sticker], 2, Money::cents(900));

        self::assertSame([], $this->calculator->calculate([$this->line($this->sticker, 2)], [$rule]));
    }

    public function testInactiveRulesAreIgnored(): void
    {
        $rule = DiscountRule::create('3 stickers pour 10 €', [$this->sticker], 3, Money::cents(1_000));
        $rule->deactivate();

        self::assertSame([], $this->calculator->calculate([$this->line($this->sticker, 3)], [$rule]));
    }

    public function testBestSavingWinsWhenRulesCompeteForTheSameUnits(): void
    {
        $small = DiscountRule::create('2 stickers pour 7 €', [$this->sticker], 2, Money::cents(700)); // saves 1 € per 2
        $big = DiscountRule::create('3 stickers pour 10 €', [$this->sticker], 3, Money::cents(1_000)); // saves 2 € per 3

        // 5 stickers: big bundle first (2 €), remaining 2 → small bundle (1 €)
        $discounts = $this->calculator->calculate([$this->line($this->sticker, 5)], [$small, $big]);

        self::assertEquals([
            new AppliedDiscount('3 stickers pour 10 €', Money::cents(200)),
            new AppliedDiscount('2 stickers pour 7 €', Money::cents(100)),
        ], $discounts);
    }

    public function testUnitIsUsedByOneBundleOnly(): void
    {
        $stickers = DiscountRule::create('3 stickers pour 10 €', [$this->sticker], 3, Money::cents(1_000));
        $everything = DiscountRule::create('Sticker + print', [$this->sticker, $this->print], 2, Money::cents(1_700));

        // print 15 + sticker 4 = 19 → 17 (saves 2 €), then 2 stickers left: no sticker bundle.
        $discounts = $this->calculator->calculate([$this->line($this->sticker, 3), $this->line($this->print, 1)], [$stickers, $everything]);

        $total = Money::sum(array_map(static fn (AppliedDiscount $discount): Money => $discount->amount, $discounts));
        self::assertSame(200, $total->amount());
        self::assertCount(1, $discounts);
    }

    private function line(Product $product, int $quantity): BasketLine
    {
        return new BasketLine($product->id(), $product->sellingPrice(), $quantity);
    }
}
