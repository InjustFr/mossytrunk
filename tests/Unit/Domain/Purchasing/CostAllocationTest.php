<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Purchasing;

use App\Domain\Purchasing\CostAllocation;
use App\Domain\Shared\Money;
use PHPUnit\Framework\TestCase;

final class CostAllocationTest extends TestCase
{
    public function testEqualSharesSpreadTheLeftoverCentsOnTheFirstParts(): void
    {
        self::assertSame([100, 100, 100, 100, 100], self::cents(CostAllocation::equally(Money::cents(500), 5)));
        self::assertSame([34, 33, 33], self::cents(CostAllocation::equally(Money::cents(100), 3)));
    }

    public function testProportionalSharesFollowTheWeightsAndAddUpExactly(): void
    {
        $shares = CostAllocation::proportionally(Money::cents(1_000), [Money::cents(3_000), Money::cents(1_000)]);

        self::assertSame([750, 250], self::cents($shares));
    }

    public function testProportionalSharesGiveTheLeftoverToTheLargestRemainders(): void
    {
        $shares = CostAllocation::proportionally(Money::cents(100), [Money::cents(1), Money::cents(1), Money::cents(1)]);

        self::assertSame(100, array_sum(self::cents($shares)));
    }

    public function testWeightlessLinesShareEqually(): void
    {
        self::assertSame([50, 50], self::cents(CostAllocation::proportionally(Money::cents(100), [Money::zero(), Money::zero()])));
    }

    /**
     * @param list<Money> $shares
     *
     * @return list<int>
     */
    private static function cents(array $shares): array
    {
        return array_map(static fn (Money $share): int => $share->amount(), $shares);
    }
}
