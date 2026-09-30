<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Stock;

use App\Domain\Event\Event;
use App\Domain\Product\Product;
use App\Domain\Shared\DateRange;
use App\Domain\Shared\Money;
use App\Domain\Stock\Exception\InvalidStock;
use App\Domain\Stock\LotOrigin;
use App\Domain\Stock\StockCheck;
use App\Domain\Stock\StockCount;
use App\Domain\Stock\StockItem;
use App\Tests\Support\Costs;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;

final class StockCheckTest extends TestCase
{
    private Event $event;
    private Product $sticker;
    private StockItem $stock;

    protected function setUp(): void
    {
        $this->event = Event::schedule(TestWorkspace::get(), 'Japan Expo', 'Villepinte', DateRange::fromDates(new \DateTimeImmutable('2026-07-09'), new \DateTimeImmutable('2026-07-12')));
        $this->sticker = Costs::bought(Product::create(TestWorkspace::get(), 'STK', 'Sticker', Money::cents(400)), 100);
        $this->stock = StockItem::open($this->sticker, null);
        $this->stock->receive(10, Money::cents(1_000), LotOrigin::Purchase, new \DateTimeImmutable('2026-07-01'));
    }

    public function testMissingUnitsAreUnexplainedUntilAnOrderExplainsThem(): void
    {
        $check = $this->check(7);

        self::assertSame(3, $check->unexplainedUnits());
        self::assertSame(7, $this->stock->onHand());

        $explained = $check->explain($this->sticker->id(), null, 2);

        self::assertSame(2, $explained->quantity);
        self::assertSame(200, $explained->cost->amount());
        self::assertSame(1, $check->unexplainedUnits());
    }

    public function testAnOrderCannotExplainMoreThanWhatIsMissing(): void
    {
        $check = $this->check(9);

        self::assertSame(1, $check->explain($this->sticker->id(), null, 4)->quantity);
        self::assertSame(0, $check->unexplainedUnits());
    }

    public function testSurplusIsNotAMissingOrder(): void
    {
        $check = $this->check(12);

        self::assertSame(0, $check->unexplainedUnits());
        self::assertSame(2, $check->lines()[0]->surplus());
    }

    public function testDismissedDiscrepancyIsNoLongerUnexplained(): void
    {
        $check = $this->check(8);

        $check->dismiss($check->lines()[0]->id());

        self::assertSame(0, $check->unexplainedUnits());
        self::assertTrue($check->lines()[0]->isDismissed());
    }

    public function testAMatchingCountHasNothingToDismiss(): void
    {
        $check = $this->check(10);

        $this->expectException(InvalidStock::class);

        $check->dismiss($check->lines()[0]->id());
    }

    public function testAnItemCannotBeCountedTwice(): void
    {
        $this->expectException(InvalidStock::class);

        StockCheck::take($this->event, new \DateTimeImmutable('2026-07-13'), [
            new StockCount($this->stock, 7, Money::zero()),
            new StockCount($this->stock, 6, Money::zero()),
        ]);
    }

    public function testACheckCountsAtLeastOneItem(): void
    {
        $this->expectException(InvalidStock::class);

        StockCheck::take($this->event, new \DateTimeImmutable('2026-07-13'), []);
    }

    private function check(int $counted): StockCheck
    {
        return StockCheck::take($this->event, new \DateTimeImmutable('2026-07-13'), [new StockCount($this->stock, $counted, $this->sticker->buyingPrice())]);
    }
}
