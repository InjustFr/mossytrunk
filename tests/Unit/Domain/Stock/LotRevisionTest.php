<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Stock;

use App\Domain\Shared\Money;
use App\Domain\Stock\LotRevision;
use PHPUnit\Framework\TestCase;

final class LotRevisionTest extends TestCase
{
    public function testUnitsTakenAtTheOldUnitCostAreRecostedAtTheNewOne(): void
    {
        $revision = new LotRevision(Money::cents(2_000), 1_000, Money::cents(2_000), 500);

        self::assertTrue($revision->changesUnitCost());
        self::assertTrue($revision->drewFrom(3, Money::cents(6)));
        self::assertSame(12, $revision->costOf(3)->amount());
    }

    public function testUnitsTakenAtAnotherCostDidNotComeFromTheLot(): void
    {
        $revision = new LotRevision(Money::cents(2_000), 1_000, Money::cents(2_000), 500);

        self::assertFalse($revision->drewFrom(3, Money::cents(15)));
        self::assertFalse($revision->drewFrom(3, Money::zero()));
    }

    public function testRoundedUnitCostsStillMatchTheLot(): void
    {
        $revision = new LotRevision(Money::cents(1_000), 3, Money::cents(1_200), 3);

        self::assertTrue($revision->drewFrom(1, Money::cents(333)));
        self::assertTrue($revision->drewFrom(2, Money::cents(667)));
        self::assertFalse($revision->drewFrom(2, Money::cents(669)));
    }

    public function testTheSameUnitCostIsNoRevision(): void
    {
        self::assertFalse(new LotRevision(Money::cents(1_000), 10, Money::cents(2_000), 20)->changesUnitCost());
    }
}
