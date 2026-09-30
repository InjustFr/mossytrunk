<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Discount;

use App\Domain\Discount\ConditionSpec;
use App\Domain\Discount\DiscountAction;
use App\Domain\Discount\DiscountCondition;
use App\Domain\Discount\DiscountRule;
use App\Domain\Discount\DiscountStatus;
use App\Domain\Discount\Exception\DuplicateConditionTarget;
use App\Domain\Discount\Exception\InvalidDiscountRule;
use App\Domain\Discount\Exception\OnlyEligibleProduct;
use App\Domain\Discount\ValidityPeriod;
use App\Domain\Product\Exception\UnknownTypeVariant;
use App\Domain\Product\Exception\UnknownVariant;
use App\Domain\Product\Product;
use App\Domain\Product\ProductType;
use App\Domain\Shared\Money;
use App\Tests\Support\DomainExceptions;
use App\Tests\Support\TestProductType;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;

final class DiscountRuleTest extends TestCase
{
    private ProductType $print;
    private Product $sticker;
    private Product $holo;

    protected function setUp(): void
    {
        $this->print = ProductType::create(TestWorkspace::get(), 'Print', 'PRI');
        $this->sticker = Product::create(TestWorkspace::get(), 'STK', 'Sticker', Money::cents(400), TestProductType::get());
        $this->holo = Product::create(TestWorkspace::get(), 'HOLO', 'Holo', Money::cents(600), TestProductType::get());
    }

    public function testConditionsDescribeTheirTarget(): void
    {
        $rule = $this->rule([new ConditionSpec(2, $this->print), new ConditionSpec(1, $this->sticker)]);

        self::assertSame(
            [['type', 'Print', 2], ['product', 'Sticker', 1]],
            array_map(static fn (DiscountCondition $c): array => [$c->kind(), $c->targetName(), $c->quantity()], $rule->conditions()),
        );
    }

    public function testNeedsANameAndAtLeastOneCondition(): void
    {
        $this->expectException(InvalidDiscountRule::class);

        $this->rule([]);
    }

    public function testNameIsRequired(): void
    {
        $this->expectException(InvalidDiscountRule::class);

        DiscountRule::create(TestWorkspace::get(), '  ', [new ConditionSpec(1, $this->sticker)], DiscountAction::amountOff(Money::cents(100)));
    }

    public function testConditionQuantityIsAtLeastOne(): void
    {
        $this->expectException(InvalidDiscountRule::class);

        $this->rule([new ConditionSpec(0, $this->sticker)]);
    }

    public function testATargetAppearsInOneConditionOnly(): void
    {
        $this->expectExceptionObject(new DuplicateConditionTarget('Print'));

        $this->rule([new ConditionSpec(1, $this->print), new ConditionSpec(2, $this->print)]);
    }

    public function testValidityEndCannotPrecedeItsStart(): void
    {
        $this->expectException(InvalidDiscountRule::class);

        ValidityPeriod::between(new \DateTimeImmutable('2026-07-10'), new \DateTimeImmutable('2026-07-09'));
    }

    public function testAppliesWithinItsPeriod(): void
    {
        $rule = $this->summer(new \DateTimeImmutable('2026-07-01'), null);

        self::assertFalse($rule->appliesOn(new \DateTimeImmutable('2026-06-30 12:00')));
        self::assertTrue($rule->appliesOn(new \DateTimeImmutable('2027-01-01 12:00')));
    }

    public function testStatusFollowsTheDates(): void
    {
        $rule = $this->summer(new \DateTimeImmutable('2026-07-01'), new \DateTimeImmutable('2026-07-31'));

        self::assertSame(DiscountStatus::Upcoming, $rule->statusOn(new \DateTimeImmutable('2026-06-30 12:00')));
        self::assertSame(DiscountStatus::Running, $rule->statusOn(new \DateTimeImmutable('2026-07-31 23:00', new \DateTimeZone('Europe/Paris'))));
        self::assertSame(DiscountStatus::Expired, $rule->statusOn(new \DateTimeImmutable('2026-08-01 12:00')));
    }

    public function testStoppingARunningDiscountEndsItYesterday(): void
    {
        $rule = $this->summer(new \DateTimeImmutable('2026-07-01'), null);

        $rule->stopBefore(new \DateTimeImmutable('2026-09-29 12:00'));

        self::assertSame('2026-09-28', $rule->validity()->end()?->format('Y-m-d'));
        self::assertSame(DiscountStatus::Expired, $rule->statusOn(new \DateTimeImmutable('2026-09-29 12:00')));
    }

    public function testADiscountStartedTodayCannotBeStopped(): void
    {
        $rule = $this->summer(new \DateTimeImmutable('2026-09-29'), null);

        $this->expectException(InvalidDiscountRule::class);

        $rule->stopBefore(new \DateTimeImmutable('2026-09-29 12:00'));
    }

    public function testStartingAnUpcomingDiscountMovesItsStartToToday(): void
    {
        $rule = $this->summer(new \DateTimeImmutable('2026-12-01'), new \DateTimeImmutable('2026-12-31'));

        $rule->startOn(new \DateTimeImmutable('2026-09-29 12:00'));

        self::assertSame(['2026-09-29', '2026-12-31'], [$rule->validity()->start()?->format('Y-m-d'), $rule->validity()->end()?->format('Y-m-d')]);
    }

    public function testAnExpiredDiscountCannotBeStartedAgain(): void
    {
        $rule = $this->summer(new \DateTimeImmutable('2026-07-01'), new \DateTimeImmutable('2026-07-31'));

        $this->expectException(InvalidDiscountRule::class);

        $rule->startOn(new \DateTimeImmutable('2026-09-29 12:00'));
    }

    private function summer(?\DateTimeImmutable $start, ?\DateTimeImmutable $end): DiscountRule
    {
        return DiscountRule::create(TestWorkspace::get(), 'Été', [new ConditionSpec(1, $this->sticker)], DiscountAction::amountOff(Money::cents(100)), ValidityPeriod::between($start, $end));
    }

    public function testAReplacedProductIsRetargetedOrMergedWithTheTarget(): void
    {
        $other = Product::create(TestWorkspace::get(), 'OTH', 'Autre', Money::cents(400), TestProductType::get());
        $retargeted = $this->rule([new ConditionSpec(2, $this->sticker)]);
        $merged = $this->rule([new ConditionSpec(2, $this->sticker), new ConditionSpec(1, $this->holo)]);

        $retargeted->replaceProduct($this->sticker, null, $other, null);
        $merged->replaceProduct($this->sticker, null, $this->holo, null);

        self::assertSame([['Autre', 2]], $this->targets($retargeted));
        self::assertSame([['Holo', 3]], $this->targets($merged));
    }

    public function testAProductCanBeWithdrawnUnlessItIsTheOnlyCondition(): void
    {
        $both = $this->rule([new ConditionSpec(1, $this->sticker), new ConditionSpec(1, $this->holo)]);
        $both->withdrawProduct($this->holo);
        self::assertSame([['Sticker', 1]], $this->targets($both));

        $this->expectException(InvalidDiscountRule::class);
        $both->withdrawProduct($this->sticker);
    }

    public function testEveryProductCanBeWithdrawnWhenTypesRemain(): void
    {
        $rule = $this->rule([new ConditionSpec(1, $this->sticker), new ConditionSpec(2, $this->print)]);

        $rule->withdrawEveryProduct();

        self::assertSame([['Print', 2]], $this->targets($rule));
    }

    public function testConditionsCanTargetAVariantOfATypeOrOfAProduct(): void
    {
        $forest = $this->print('Forêt', ['A4', 'A3']);

        $rule = $this->rule([new ConditionSpec(1, $this->print, 'a3'), new ConditionSpec(2, $forest, ' a4 '), new ConditionSpec(1, $this->print)]);

        self::assertSame(
            [['type', 'Print · A3', 'A3', 1], ['product', 'Print Forêt · A4', 'A4', 2], ['type', 'Print', null, 1]],
            array_map(static fn (DiscountCondition $c): array => [$c->kind(), $c->targetName(), $c->variant(), $c->quantity()], $rule->conditions()),
        );
    }

    public function testConditionsFromTheMostToTheLeastSpecific(): void
    {
        $forest = $this->print('Forêt', ['A3']);

        $rule = $this->rule([new ConditionSpec(1, $this->print), new ConditionSpec(1, $this->print, 'A3'), new ConditionSpec(1, $forest), new ConditionSpec(1, $forest, 'A3')]);

        self::assertSame(
            [['Print Forêt · A3', 3], ['Print Forêt', 2], ['Print · A3', 1], ['Print', 0]],
            array_map(static fn (DiscountCondition $c): array => [$c->targetName(), $c->specificity()], $rule->conditionsMostSpecificFirst()),
        );
    }

    public function testAConditionVariantMustBeOneOfTheType(): void
    {
        $this->print('Forêt', ['A3']);

        DomainExceptions::assertThrown(new UnknownTypeVariant('Print', 'A5'), fn () => $this->rule([new ConditionSpec(1, $this->print, 'A5')]));
    }

    public function testAConditionVariantMustBeOneOfTheProduct(): void
    {
        $this->print('Rivière', ['A3']);
        $forest = $this->print('Forêt', ['A4']);

        DomainExceptions::assertThrown(new UnknownVariant('Print Forêt', 'A3'), fn () => $this->rule([new ConditionSpec(1, $forest, 'A3')]));
    }

    public function testATargetWithTheSameVariantAppearsInOneConditionOnly(): void
    {
        $forest = $this->print('Forêt', ['A4', 'A3']);
        $this->rule([new ConditionSpec(1, $forest), new ConditionSpec(1, $forest, 'A3'), new ConditionSpec(1, $forest, 'A4'), new ConditionSpec(1, $this->print, 'A3')]);

        DomainExceptions::assertThrown(new DuplicateConditionTarget('Print Forêt · A3'), fn () => $this->rule([new ConditionSpec(1, $forest, 'A3'), new ConditionSpec(2, $forest, 'a3')]));
        DomainExceptions::assertThrown(new DuplicateConditionTarget('Print · A3'), fn () => $this->rule([new ConditionSpec(1, $this->print, 'A3'), new ConditionSpec(2, $this->print, 'a3')]));
    }

    public function testConditionsOnAMovedVariantFollowItAndOthersFollowWhenTheTargetHasTheirVariant(): void
    {
        $old = $this->print('Vieux', ['A4', 'A3', 'A5']);
        $forest = $this->print('Forêt', ['a3', 'Carré']);
        $rule = $this->rule([new ConditionSpec(1, $old), new ConditionSpec(2, $old, 'A4'), new ConditionSpec(3, $old, 'A3'), new ConditionSpec(4, $old, 'A5')]);

        $rule->replaceProduct($old, 'A4', $forest, 'Carré');

        self::assertSame([['Print Forêt · Carré', 3], ['Print Forêt · A3', 3]], $this->targets($rule));
    }

    public function testAMovedConditionMergesWithTheSameConditionOnTheTarget(): void
    {
        $old = $this->print('Vieux', ['A4']);
        $forest = $this->print('Forêt', ['A4']);
        $rule = $this->rule([new ConditionSpec(2, $old, 'A4'), new ConditionSpec(1, $forest, 'A4')]);

        $rule->replaceProduct($old, 'A4', $forest, 'A4');

        self::assertSame([['Print Forêt · A4', 3]], $this->targets($rule));
    }

    public function testARuleLeftWithoutConditionAfterAMoveIsRefused(): void
    {
        $old = $this->print('Vieux', ['A4', 'A3']);
        $forest = $this->print('Forêt', ['A5']);
        $rule = $this->rule([new ConditionSpec(1, $old, 'A3')]);

        DomainExceptions::assertThrown(new OnlyEligibleProduct('Remise', 'Print Vieux'), static fn () => $rule->replaceProduct($old, 'A4', $forest, 'A5'));
    }

    public function testRenamingAVariantRenamesTheConditionsOfItsTypeOnly(): void
    {
        $forest = $this->print('Forêt', ['A3']);
        $shirts = ProductType::create(TestWorkspace::get(), 'T-shirt', 'TSH');
        $shirt = Product::create(TestWorkspace::get(), 'TSH-MOU', 'Mousse', Money::cents(2_000), $shirts, ['A3']);
        $rule = $this->rule([new ConditionSpec(1, $this->print, 'A3'), new ConditionSpec(1, $forest, 'A3'), new ConditionSpec(1, $shirts, 'A3'), new ConditionSpec(1, $shirt, 'A3')]);

        $rule->renameVariant($this->print, 'a3', 'Grand');

        self::assertSame(['Grand', 'Grand', 'A3', 'A3'], array_map(static fn (DiscountCondition $c): ?string => $c->variant(), $rule->conditions()));
    }

    public function testARuleUsesAVariantThroughATypeOrAProductCondition(): void
    {
        $forest = $this->print('Forêt', ['A4', 'A3']);
        $shirts = ProductType::create(TestWorkspace::get(), 'T-shirt', 'TSH');
        $shirts->defineVariants(['A5']);
        $onType = $this->rule([new ConditionSpec(1, $this->print, 'A3')]);
        $onProduct = $this->rule([new ConditionSpec(1, $forest, 'A4'), new ConditionSpec(1, $shirts, 'A5')]);
        $withoutVariant = $this->rule([new ConditionSpec(1, $this->print), new ConditionSpec(1, $forest)]);

        self::assertTrue($onType->usesVariant($this->print, 'a3'));
        self::assertFalse($onType->usesVariant($this->print, 'A4'));
        self::assertTrue($onProduct->usesVariant($this->print, 'A4'));
        self::assertFalse($onProduct->usesVariant($this->print, 'A5'));
        self::assertTrue($onProduct->usesVariant($shirts, 'A5'));
        self::assertFalse($withoutVariant->usesVariant($this->print, 'A3'));
    }

    /**
     * @param list<string> $variants
     */
    private function print(string $name, array $variants = []): Product
    {
        return Product::create(TestWorkspace::get(), 'PRI-'.$name, $name, Money::cents(1_500), $this->print, $variants);
    }

    /**
     * @param list<ConditionSpec> $conditions
     */
    private function rule(array $conditions): DiscountRule
    {
        return DiscountRule::create(TestWorkspace::get(), 'Remise', $conditions, DiscountAction::fixedPrice(Money::cents(1_000)));
    }

    /**
     * @return list<array{string, int}>
     */
    private function targets(DiscountRule $rule): array
    {
        return array_map(static fn (DiscountCondition $c): array => [$c->targetName(), $c->quantity()], $rule->conditions());
    }
}
