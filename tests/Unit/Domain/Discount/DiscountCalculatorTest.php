<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Discount;

use App\Domain\Discount\AppliedDiscount;
use App\Domain\Discount\BasketLine;
use App\Domain\Discount\ConditionSpec;
use App\Domain\Discount\DiscountAction;
use App\Domain\Discount\DiscountCalculator;
use App\Domain\Discount\DiscountRule;
use App\Domain\Discount\ValidityPeriod;
use App\Domain\Product\Product;
use App\Domain\Product\ProductType;
use App\Domain\Shared\Money;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;

final class DiscountCalculatorTest extends TestCase
{
    private ProductType $printType;
    private ProductType $stickerType;
    private Product $forest;
    private Product $river;
    private Product $sticker;
    private Product $holoSticker;
    private Product $tshirt;
    private DiscountCalculator $calculator;
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->printType = ProductType::create(TestWorkspace::get(), 'Print', 'PRI');
        $this->stickerType = ProductType::create(TestWorkspace::get(), 'Sticker', 'STI');
        $this->forest = Product::create(TestWorkspace::get(), 'PRI-FORET', 'Forêt', Money::cents(1_500), variants: ['A4', 'A3'], type: $this->printType);
        $this->river = Product::create(TestWorkspace::get(), 'PRI-RIVIERE', 'Rivière', Money::cents(1_500), type: $this->printType);
        $this->sticker = Product::create(TestWorkspace::get(), 'STI-MOUSSE', 'Mousse', Money::cents(400), type: $this->stickerType);
        $this->holoSticker = Product::create(TestWorkspace::get(), 'STI-HOLO', 'Holo', Money::cents(600), type: $this->stickerType);
        $this->tshirt = Product::create(TestWorkspace::get(), 'TSH', 'T-shirt', Money::cents(2_500));
        $this->calculator = new DiscountCalculator();
        $this->now = new \DateTimeImmutable('2026-07-10 15:00', new \DateTimeZone('Europe/Paris'));
    }

    public function testTwoPrintsAndOneStickerForAFixedPrice(): void
    {
        $rule = $this->rule('2 prints et 1 sticker pour 15 €', [new ConditionSpec(2, $this->printType), new ConditionSpec(1, $this->stickerType)], DiscountAction::fixedPrice(Money::cents(1_500)));

        $discounts = $this->calculator->calculate([$this->line($this->forest, 1), $this->line($this->river, 1), $this->line($this->sticker, 1)], [$rule], $this->now);

        self::assertEquals([new AppliedDiscount('2 prints et 1 sticker pour 15 €', Money::cents(1_900), $rule->id())], $discounts);
    }

    public function testEveryConditionMustBeMet(): void
    {
        $rule = $this->rule('2 prints et 1 sticker', [new ConditionSpec(2, $this->printType), new ConditionSpec(1, $this->stickerType)], DiscountAction::fixedPrice(Money::cents(1_500)));

        self::assertSame([], $this->calculator->calculate([$this->line($this->forest, 1), $this->line($this->sticker, 3)], [$rule], $this->now));
    }

    public function testRuleAppliesAsOftenAsTheBasketAllows(): void
    {
        $rule = $this->rule('2 prints et 1 sticker', [new ConditionSpec(2, $this->printType), new ConditionSpec(1, $this->stickerType)], DiscountAction::fixedPrice(Money::cents(3_000)));

        $discounts = $this->calculator->calculate([$this->line($this->forest, 5), $this->line($this->sticker, 2)], [$rule], $this->now);

        self::assertEquals([new AppliedDiscount('2 prints et 1 sticker ×2', Money::cents(800), $rule->id())], $discounts);
    }

    public function testProductConditionOnlyMatchesThatProductWhateverTheVariant(): void
    {
        $rule = $this->rule('2 Forêt pour 25 €', [new ConditionSpec(2, $this->forest)], DiscountAction::fixedPrice(Money::cents(2_500)));

        self::assertSame([], $this->calculator->calculate([$this->line($this->forest, 1), $this->line($this->river, 1)], [$rule], $this->now));
        self::assertCount(1, $this->calculator->calculate([$this->line($this->forest, 2)], [$rule], $this->now));
    }

    public function testProductConditionsPickTheirUnitsBeforeTypeConditions(): void
    {
        $rule = $this->rule('1 Holo et 1 sticker', [new ConditionSpec(1, $this->stickerType), new ConditionSpec(1, $this->holoSticker)], DiscountAction::fixedPrice(Money::cents(800)));

        $discounts = $this->calculator->calculate([$this->line($this->holoSticker, 1), $this->line($this->sticker, 1)], [$rule], $this->now);

        self::assertEquals([new AppliedDiscount('1 Holo et 1 sticker', Money::cents(200), $rule->id())], $discounts);
    }

    public function testMostExpensiveMatchingUnitsAreTakenFirst(): void
    {
        $rule = $this->rule('−50 % sur 1 sticker', [new ConditionSpec(1, $this->stickerType)], DiscountAction::percentOff(5_000));

        $discounts = $this->calculator->calculate([$this->line($this->sticker, 1), $this->line($this->holoSticker, 1)], [$rule], $this->now);

        self::assertEquals([new AppliedDiscount('−50 % sur 1 sticker ×2', Money::cents(500), $rule->id())], $discounts);
    }

    public function testAmountOffIsCappedAtTheItemsPrice(): void
    {
        $rule = $this->rule('Sticker −10 €', [new ConditionSpec(1, $this->sticker)], DiscountAction::amountOff(Money::cents(1_000)));

        self::assertEquals([new AppliedDiscount('Sticker −10 €', Money::cents(400), $rule->id())], $this->calculator->calculate([$this->line($this->sticker, 1)], [$rule], $this->now));
    }

    public function testFixedPriceAboveTheRegularPriceIsNotApplied(): void
    {
        $rule = $this->rule('2 stickers pour 10 €', [new ConditionSpec(2, $this->stickerType)], DiscountAction::fixedPrice(Money::cents(1_000)));

        self::assertSame([], $this->calculator->calculate([$this->line($this->sticker, 2)], [$rule], $this->now));
    }

    public function testBestSavingWinsWhenRulesCompeteForTheSameUnits(): void
    {
        $small = $this->rule('A: T-shirt −2 €', [new ConditionSpec(1, $this->tshirt)], DiscountAction::amountOff(Money::cents(200)));
        $big = $this->rule('B: T-shirt −20 %', [new ConditionSpec(1, $this->tshirt)], DiscountAction::percentOff(2_000));

        $discounts = $this->calculator->calculate([$this->line($this->tshirt, 1)], [$small, $big], $this->now);

        self::assertEquals([new AppliedDiscount('B: T-shirt −20 %', Money::cents(500), $big->id())], $discounts);
    }

    public function testRulesOnlyApplyWithinTheirValidityPeriodBoundsIncluded(): void
    {
        $rule = $this->rule(
            'T-shirt −2 €',
            [new ConditionSpec(1, $this->tshirt)],
            DiscountAction::amountOff(Money::cents(200)),
            ValidityPeriod::between(new \DateTimeImmutable('2026-07-10'), new \DateTimeImmutable('2026-07-11')),
        );
        $basket = [$this->line($this->tshirt, 1)];
        $paris = new \DateTimeZone('Europe/Paris');

        self::assertSame([], $this->calculator->calculate($basket, [$rule], new \DateTimeImmutable('2026-07-09 23:59', $paris)));
        self::assertCount(1, $this->calculator->calculate($basket, [$rule], new \DateTimeImmutable('2026-07-10 00:01', $paris)));
        self::assertCount(1, $this->calculator->calculate($basket, [$rule], new \DateTimeImmutable('2026-07-11 23:59', $paris)));
        self::assertSame([], $this->calculator->calculate($basket, [$rule], new \DateTimeImmutable('2026-07-12 00:01', $paris)));
    }

    /**
     * @param list<ConditionSpec> $conditions
     */
    private function rule(string $name, array $conditions, DiscountAction $action, ?ValidityPeriod $validity = null): DiscountRule
    {
        return DiscountRule::create(TestWorkspace::get(), $name, $conditions, $action, $validity);
    }

    private function line(Product $product, int $quantity): BasketLine
    {
        return new BasketLine($product->id(), $product->sellingPrice(), $quantity, $product->type()?->id());
    }
}
