<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Discount;

use App\Domain\Discount\ConditionSpec;
use App\Domain\Discount\DiscountAction;
use App\Domain\Discount\DiscountCondition;
use App\Domain\Discount\DiscountRule;
use App\Domain\Discount\DiscountStatus;
use App\Domain\Discount\InvalidDiscountRule;
use App\Domain\Discount\ValidityPeriod;
use App\Domain\Product\Product;
use App\Domain\Product\ProductType;
use App\Domain\Shared\Money;
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
        $this->sticker = Product::create(TestWorkspace::get(), 'STK', 'Sticker', Money::cents(400));
        $this->holo = Product::create(TestWorkspace::get(), 'HOLO', 'Holo', Money::cents(600));
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
        $this->expectExceptionMessage('« Print » apparaît dans plusieurs conditions');

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
        $other = Product::create(TestWorkspace::get(), 'OTH', 'Autre', Money::cents(400));
        $retargeted = $this->rule([new ConditionSpec(2, $this->sticker)]);
        $merged = $this->rule([new ConditionSpec(2, $this->sticker), new ConditionSpec(1, $this->holo)]);

        $retargeted->replaceProduct($this->sticker, $other);
        $merged->replaceProduct($this->sticker, $this->holo);

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
