<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Shared;

use App\Domain\Shared\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testArithmetic(): void
    {
        $money = Money::cents(1_250)->add(Money::cents(250))->subtract(Money::cents(500))->multiply(3);

        self::assertSame(3_000, $money->amount());
    }

    public function testSumOfAmounts(): void
    {
        self::assertSame(600, Money::sum([Money::cents(100), Money::cents(200), Money::cents(300)])->amount());
        self::assertTrue(Money::sum([])->isZero());
    }

    /**
     * @return iterable<string, array{int, int, int}>
     */
    public static function percentages(): iterable
    {
        yield '12.8 % of 100 €' => [10_000, 1_280, 1_280];
        yield 'exact result' => [1_250, 1_280, 160];
        yield 'rounds 0.5 cent up' => [125, 1_000, 13]; // 12.5
        yield 'rounds to nearest cent' => [1_234, 1_280, 158]; // 157.952 → 158
        yield 'negative amounts round away from zero' => [-125, 1_000, -13];
    }

    #[DataProvider('percentages')]
    public function testPercentageInBasisPoints(int $cents, int $basisPoints, int $expected): void
    {
        self::assertSame($expected, Money::cents($cents)->percentage($basisPoints)->amount());
    }

    public function testComparisons(): void
    {
        self::assertTrue(Money::cents(-1)->isNegative());
        self::assertTrue(Money::cents(1)->isPositive());
        self::assertTrue(Money::zero()->isZero());
        self::assertTrue(Money::cents(2)->greaterThan(Money::cents(1)));
        self::assertTrue(Money::cents(1)->lessThan(Money::cents(2)));
        self::assertTrue(Money::cents(5)->equals(Money::cents(5)));
    }
}
