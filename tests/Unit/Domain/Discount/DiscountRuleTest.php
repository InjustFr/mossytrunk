<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Discount;

use App\Domain\Discount\DiscountRule;
use App\Domain\Discount\InvalidDiscountRule;
use App\Domain\Product\Product;
use App\Domain\Product\ProductType;
use App\Domain\Shared\InvalidMoney;
use App\Domain\Shared\Money;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

final class DiscountRuleTest extends TestCase
{
    public function testEligibility(): void
    {
        $sticker = Product::create(TestWorkspace::get(), 'STK', 'Sticker', Money::cents(400));
        $rule = DiscountRule::create(TestWorkspace::get(), '3 pour 10', [$sticker, $sticker], 3, Money::cents(1_000));

        self::assertTrue($rule->isEligible($sticker->id()));
        self::assertFalse($rule->isEligible(new Ulid()));
        self::assertCount(1, $rule->eligibleProducts());
        self::assertTrue($rule->isActive());
    }

    public function testTypeMakesItsProductsEligible(): void
    {
        $print = ProductType::create(TestWorkspace::get(), 'Print', 'PRI');
        $rule = DiscountRule::create(TestWorkspace::get(), '2 prints', [], 2, Money::cents(2_500), [$print]);

        self::assertTrue($rule->isEligible(new Ulid(), $print->id()));
        self::assertFalse($rule->isEligible(new Ulid(), new Ulid()));
        self::assertFalse($rule->isEligible(new Ulid()));
    }

    public function testNeedsAtLeastOneProductOrType(): void
    {
        $this->expectException(InvalidDiscountRule::class);

        DiscountRule::create(TestWorkspace::get(), '3 pour 10', [], 3, Money::cents(1_000));
    }

    public function testBundleHasAtLeastTwoUnits(): void
    {
        $this->expectException(InvalidDiscountRule::class);

        DiscountRule::create(TestWorkspace::get(), '1 pour 3', [Product::create(TestWorkspace::get(), 'STK', 'Sticker', Money::cents(400))], 1, Money::cents(300));
    }

    public function testBundlePriceMustBePositive(): void
    {
        $this->expectException(InvalidMoney::class);

        DiscountRule::create(TestWorkspace::get(), 'Gratuit', [Product::create(TestWorkspace::get(), 'STK', 'Sticker', Money::cents(400))], 2, Money::zero());
    }

    public function testCanBeToggled(): void
    {
        $rule = DiscountRule::create(TestWorkspace::get(), '3 pour 10', [Product::create(TestWorkspace::get(), 'STK', 'Sticker', Money::cents(400))], 3, Money::cents(1_000));

        $rule->deactivate();
        self::assertFalse($rule->isActive());
        $rule->activate();
        self::assertTrue($rule->isActive());
    }
}
