<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Sales;

use App\Domain\Sales\PriceAdjustment;
use App\Domain\Shared\Money;
use PHPUnit\Framework\TestCase;

final class PriceAdjustmentTest extends TestCase
{
    public function testAddsAnAmountOrAPercentageRoundedToTheCent(): void
    {
        self::assertSame(1_500, PriceAdjustment::none()->applyTo(Money::cents(1_500))->amount());
        self::assertSame(1_600, PriceAdjustment::byCents(100)->applyTo(Money::cents(1_500))->amount());
        self::assertSame(1_400, PriceAdjustment::byCents(-100)->applyTo(Money::cents(1_500))->amount());
        self::assertSame(1_650, PriceAdjustment::byBasisPoints(1_000)->applyTo(Money::cents(1_500))->amount());
        self::assertSame(367, PriceAdjustment::byBasisPoints(-833)->applyTo(Money::cents(400))->amount());
    }
}
