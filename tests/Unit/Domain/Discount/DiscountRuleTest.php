<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Discount;

use App\Domain\Discount\DiscountRule;
use App\Domain\Discount\InvalidDiscountRule;
use App\Domain\Product\Product;
use App\Domain\Shared\InvalidMoney;
use App\Domain\Shared\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

final class DiscountRuleTest extends TestCase
{
    public function testEligibility(): void
    {
        $sticker = Product::create('STK', 'Sticker', Money::cents(400));
        $rule = DiscountRule::create('3 pour 10', [$sticker, $sticker], 3, Money::cents(1_000));

        self::assertTrue($rule->isEligible($sticker->id()));
        self::assertFalse($rule->isEligible(new Ulid()));
        self::assertCount(1, $rule->eligibleProducts());
        self::assertTrue($rule->isActive());
    }

    public function testNeedsAtLeastOneProduct(): void
    {
        $this->expectException(InvalidDiscountRule::class);

        DiscountRule::create('3 pour 10', [], 3, Money::cents(1_000));
    }

    public function testBundleHasAtLeastTwoUnits(): void
    {
        $this->expectException(InvalidDiscountRule::class);

        DiscountRule::create('1 pour 3', [Product::create('STK', 'Sticker', Money::cents(400))], 1, Money::cents(300));
    }

    public function testBundlePriceMustBePositive(): void
    {
        $this->expectException(InvalidMoney::class);

        DiscountRule::create('Gratuit', [Product::create('STK', 'Sticker', Money::cents(400))], 2, Money::zero());
    }

    public function testCanBeToggled(): void
    {
        $rule = DiscountRule::create('3 pour 10', [Product::create('STK', 'Sticker', Money::cents(400))], 3, Money::cents(1_000));

        $rule->deactivate();
        self::assertFalse($rule->isActive());
        $rule->activate();
        self::assertTrue($rule->isActive());
    }
}
