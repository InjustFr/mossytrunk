<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Stock;

use App\Domain\Product\Exception\InvalidProduct;
use App\Domain\Product\Product;
use App\Domain\Shared\Money;
use App\Domain\Stock\Exception\InvalidStock;
use App\Domain\Stock\LotOrigin;
use App\Domain\Stock\StockItem;
use App\Domain\Stock\StockLot;
use App\Tests\Support\Costs;
use App\Tests\Support\TestProductType;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

final class StockItemTest extends TestCase
{
    private Product $sticker;

    protected function setUp(): void
    {
        $this->sticker = Costs::bought(Product::create(TestWorkspace::get(), 'STK', 'Sticker', Money::cents(400), TestProductType::get()), 90);
    }

    public function testOldestLotIsSoldFirst(): void
    {
        $stock = $this->stock();
        $stock->receive(10, Money::cents(500), LotOrigin::Purchase, self::at('2026-01-01'));
        $stock->receive(10, Money::cents(1_000), LotOrigin::Purchase, self::at('2026-02-01'));

        self::assertSame(500 + 200, $stock->withdraw(12, Money::cents(90))->amount());
        self::assertSame(8, $stock->onHand());
        self::assertSame(100, $stock->nextUnitCost(Money::cents(90))->amount());
        self::assertSame(800, $stock->remainingValue()->amount());
    }

    public function testLotReceivedWithAnOlderDateIsSoldBeforeNewerOnes(): void
    {
        $stock = $this->stock();
        $stock->receive(5, Money::cents(1_000), LotOrigin::Purchase, self::at('2026-02-01'));
        $stock->receive(5, Money::cents(500), LotOrigin::Purchase, self::at('2026-01-01'));

        self::assertSame(500, $stock->withdraw(5, Money::cents(90))->amount());
    }

    public function testUnitCostsOfALotAlwaysAddUpToWhatWasPaid(): void
    {
        $stock = $this->stock();
        $stock->receive(3, Money::cents(1_000), LotOrigin::Purchase, self::at('2026-01-01'));

        $costs = [$stock->withdraw(1, Money::zero()), $stock->withdraw(1, Money::zero()), $stock->withdraw(1, Money::zero())];

        self::assertSame(1_000, Money::sum($costs)->amount());
    }

    public function testSellingWithoutStockUsesTheLastPurchasePriceAndGoesNegative(): void
    {
        $stock = $this->stock();
        $stock->receive(2, Money::cents(200), LotOrigin::Purchase, self::at('2026-01-01'));
        $stock->receive(1, Money::cents(150), LotOrigin::Purchase, self::at('2026-02-01'));

        self::assertSame(200 + 150 + 2 * 150, $stock->withdraw(5, Money::cents(90))->amount());
        self::assertSame(-2, $stock->onHand());
        self::assertTrue($stock->isNegative());
    }

    public function testSellingANeverStockedItemUsesTheFallbackCost(): void
    {
        $stock = $this->stock();

        self::assertSame(180, $stock->withdraw(2, Money::cents(90))->amount());
        self::assertSame(-2, $stock->onHand());
    }

    public function testNewLotFirstCoversUnitsSoldWithoutStock(): void
    {
        $stock = $this->stock();
        $stock->withdraw(3, Money::cents(90));
        $stock->receive(10, Money::cents(1_000), LotOrigin::Purchase, self::at('2026-03-01'));

        self::assertSame(7, $stock->onHand());
        self::assertSame(700, $stock->remainingValue()->amount());
        self::assertSame(300, $stock->withdraw(3, Money::zero())->amount());
    }

    public function testCancellingAWithdrawalPutsTheUnitsBackInTheLotsTheyCameFrom(): void
    {
        $stock = $this->stock();
        $stock->receive(10, Money::cents(500), LotOrigin::Purchase, self::at('2026-01-01'));
        $stock->receive(10, Money::cents(1_000), LotOrigin::Purchase, self::at('2026-02-01'));
        $stock->withdraw(12, Money::zero());

        $stock->cancelWithdrawal(4);

        self::assertSame(12, $stock->onHand());
        self::assertSame([2, 10], array_map(static fn (StockLot $lot): int => $lot->remaining(), $stock->lots()));
        self::assertSame(1_000 + 100, $stock->remainingValue()->amount());
    }

    public function testCancellingAWithdrawalBeyondTheLotsOnlyRaisesTheBalance(): void
    {
        $stock = $this->stock();
        $stock->receive(2, Money::cents(200), LotOrigin::Purchase, self::at('2026-01-01'));
        $stock->withdraw(5, Money::cents(90));

        $stock->cancelWithdrawal(2);

        self::assertSame(-1, $stock->onHand());
        self::assertSame(0, $stock->lots()[0]->remaining());
    }

    public function testTakenBackUnitsAreANewReturnLotAtTheirSaleCost(): void
    {
        $orderId = new Ulid();
        $stock = $this->stock();
        $stock->receive(5, Money::cents(1_000), LotOrigin::Purchase, self::at('2026-03-01'));
        $stock->takeBack(2, Money::cents(300), self::at('2026-02-01'), $orderId);

        self::assertSame(7, $stock->onHand());
        self::assertTrue($stock->lots()[0]->isReturnOf($orderId));
        self::assertSame(300, $stock->withdraw(2, Money::zero())->amount());
    }

    public function testCancellingAReturnRemovesItsLotAndTheUnitsItBrought(): void
    {
        $orderId = new Ulid();
        $stock = $this->stock();
        $stock->receive(5, Money::cents(1_000), LotOrigin::Purchase, self::at('2026-01-01'));
        $stock->takeBack(2, Money::cents(300), self::at('2026-02-01'), $orderId);
        $stock->withdraw(6, Money::zero());

        $stock->cancelReturnOf($orderId);

        self::assertSame(-1, $stock->onHand());
        self::assertCount(1, $stock->lots());
        self::assertSame(0, $stock->lots()[0]->remaining());
    }

    public function testCountingLessWithdrawsTheMissingUnits(): void
    {
        $stock = $this->stock();
        $stock->receive(10, Money::cents(1_000), LotOrigin::Purchase, self::at('2026-01-01'));

        $correction = $stock->correctTo(7, Money::cents(90), self::at('2026-04-01'));

        self::assertSame(10, $correction->expected);
        self::assertSame(3, $correction->missing());
        self::assertSame(300, $correction->lossCost->amount());
        self::assertSame(7, $stock->onHand());
    }

    public function testCountingMoreAddsUnitsAtTheNextUnitCost(): void
    {
        $stock = $this->stock();
        $stock->receive(4, Money::cents(400), LotOrigin::Purchase, self::at('2026-01-01'));

        $correction = $stock->correctTo(6, Money::cents(90), self::at('2026-04-01'));

        self::assertSame(2, $correction->surplus());
        self::assertTrue($correction->lossCost->isZero());
        self::assertSame(6, $stock->onHand());
        self::assertSame(600, $stock->remainingValue()->amount());
    }

    public function testCountingFixesANegativeStock(): void
    {
        $stock = $this->stock();
        $stock->withdraw(2, Money::cents(90));

        $stock->correctTo(3, Money::cents(90), self::at('2026-04-01'));

        self::assertSame(3, $stock->onHand());
        self::assertSame(270, $stock->remainingValue()->amount());
    }

    public function testCountCannotBeNegative(): void
    {
        $this->expectException(InvalidStock::class);

        $this->stock()->correctTo(-1, Money::zero(), self::at('2026-04-01'));
    }

    public function testReceivedQuantityMustBePositive(): void
    {
        $this->expectException(InvalidStock::class);

        $this->stock()->receive(0, Money::cents(100), LotOrigin::Purchase, self::at('2026-01-01'));
    }

    public function testAbsorbingAnotherItemKeepsItsLotsAndBalance(): void
    {
        $tshirt = Costs::bought(Product::create(TestWorkspace::get(), 'TS', 'T-shirt', Money::cents(2_000), TestProductType::get(), ['S', 'M']), 900);
        $small = StockItem::open($tshirt, 'S');
        $small->receive(3, Money::cents(2_700), LotOrigin::Purchase, self::at('2026-01-01'));
        $medium = StockItem::open($tshirt, 'M');
        $medium->withdraw(1, Money::cents(900));

        $medium->absorb($small);

        self::assertSame(2, $medium->onHand());
        self::assertSame(1_800, $medium->remainingValue()->amount());
    }

    public function testLowAtThresholdIncluded(): void
    {
        $stock = $this->stock();
        $stock->receive(10, Money::cents(1_000), LotOrigin::Purchase, self::at('2026-01-01'));

        self::assertTrue($stock->isLowAt(10));
        self::assertFalse($stock->isLowAt(9));
    }

    public function testStockItemMustMatchAVariantOfTheProduct(): void
    {
        $this->expectException(InvalidProduct::class);

        StockItem::open(Product::create(TestWorkspace::get(), 'TS', 'T-shirt', Money::cents(2_000), TestProductType::get(), ['S']), null);
    }

    private function stock(): StockItem
    {
        return StockItem::open($this->sticker, null);
    }

    private static function at(string $date): \DateTimeImmutable
    {
        return new \DateTimeImmutable($date);
    }
}
