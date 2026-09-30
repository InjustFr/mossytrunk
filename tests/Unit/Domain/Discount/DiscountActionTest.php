<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Discount;

use App\Domain\Discount\DiscountAction;
use App\Domain\Discount\Exception\InvalidDiscountRule;
use App\Domain\Shared\Exception\InvalidMoney;
use App\Domain\Shared\Money;
use PHPUnit\Framework\TestCase;

final class DiscountActionTest extends TestCase
{
    public function testFixedPriceSavesTheDifferenceWithTheRegularPrice(): void
    {
        self::assertSame(1_900, DiscountAction::fixedPrice(Money::cents(1_500))->saving(Money::cents(3_400))->amount());
        self::assertTrue(DiscountAction::fixedPrice(Money::cents(1_500))->saving(Money::cents(1_000))->isNegative());
    }

    public function testAmountOffNeverExceedsTheRegularPrice(): void
    {
        self::assertSame(500, DiscountAction::amountOff(Money::cents(500))->saving(Money::cents(3_400))->amount());
        self::assertSame(300, DiscountAction::amountOff(Money::cents(500))->saving(Money::cents(300))->amount());
    }

    public function testPercentOffIsRoundedToTheCent(): void
    {
        self::assertSame(340, DiscountAction::percentOff(1_000)->saving(Money::cents(3_400))->amount());
        self::assertSame(33, DiscountAction::percentOff(3_333)->saving(Money::cents(100))->amount());
    }

    public function testValueMustBePositive(): void
    {
        $this->expectException(InvalidMoney::class);

        DiscountAction::fixedPrice(Money::zero());
    }

    public function testPercentageIsAtMostOneHundred(): void
    {
        $this->expectException(InvalidDiscountRule::class);

        DiscountAction::percentOff(10_001);
    }
}
