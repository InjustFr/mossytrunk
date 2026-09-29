<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order;

use App\Domain\Discount\AppliedDiscount;
use App\Domain\Event\Event;
use App\Domain\Order\InvalidOrder;
use App\Domain\Order\Order;
use App\Domain\Order\OrderSource;
use App\Domain\Order\OrderedItem;
use App\Domain\Order\PaymentMethod;
use App\Domain\Product\Product;
use App\Domain\Shared\DateRange;
use App\Domain\Shared\Money;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

final class OrderTest extends TestCase
{
    private Event $event;
    private Product $sticker;
    private Product $tshirt;

    protected function setUp(): void
    {
        $this->event = Event::schedule(TestWorkspace::get(), 'Japan Expo', 'Villepinte', DateRange::fromDates(new \DateTimeImmutable('2026-07-09'), new \DateTimeImmutable('2026-07-12')));
        $this->sticker = Product::create(TestWorkspace::get(), 'STK', 'Sticker', Money::cents(400), Money::cents(80));
        $this->tshirt = Product::create(TestWorkspace::get(), 'TS', 'T-shirt', Money::cents(2_000), Money::cents(900), ['S', 'M']);
    }

    public function testTotalsAndCostOfGoods(): void
    {
        $order = Order::place($this->event, self::at('2026-07-10 15:00'), [
            new OrderedItem($this->sticker->sellable(null), 3),
            new OrderedItem($this->tshirt->sellable('M'), 1),
        ], [new AppliedDiscount('3 stickers pour 10 €', Money::cents(200))]);

        self::assertSame(3_200, $order->subtotal()->amount());
        self::assertSame(200, $order->discountTotal()->amount());
        self::assertSame(3_000, $order->total()->amount());
        self::assertSame(1_140, $order->costOfGoods()->amount());
        self::assertSame(4, $order->itemCount());
        self::assertSame(OrderSource::Manual, $order->source());
        self::assertMatchesRegularExpression('/^CMD-20260710-[0-9A-Z]{6}$/', $order->reference());
    }

    public function testSameProductVariantTupleIsMerged(): void
    {
        $order = Order::place($this->event, self::at('2026-07-10 15:00'), [
            new OrderedItem($this->tshirt->sellable('M'), 1),
            new OrderedItem($this->tshirt->sellable('S'), 1),
            new OrderedItem($this->tshirt->sellable('M'), 2),
        ], []);

        self::assertCount(2, $order->lines());
        self::assertSame(3, $order->lines()[0]->quantity());
        self::assertSame('T-shirt — M', $order->lines()[0]->label());
    }

    public function testPricesAreSnapshots(): void
    {
        $order = Order::place($this->event, self::at('2026-07-10 15:00'), [new OrderedItem($this->sticker->sellable(null), 1)], []);

        $this->sticker->reprice(Money::cents(500), Money::cents(100));

        self::assertSame(400, $order->total()->amount());
        self::assertSame(80, $order->costOfGoods()->amount());
    }

    public function testDateMustBeWithinEvent(): void
    {
        $this->expectException(InvalidOrder::class);

        Order::place($this->event, self::at('2026-07-13 10:00'), [new OrderedItem($this->sticker->sellable(null), 1)], []);
    }

    public function testLastDayOfEventIsIncluded(): void
    {
        $order = Order::place($this->event, self::at('2026-07-12 23:59'), [new OrderedItem($this->sticker->sellable(null), 1)], []);

        self::assertSame(400, $order->total()->amount());
    }

    public function testOrderNeedsAtLeastOneItem(): void
    {
        $this->expectException(InvalidOrder::class);

        Order::place($this->event, self::at('2026-07-10 15:00'), [], []);
    }

    public function testQuantityMustBePositive(): void
    {
        $this->expectException(InvalidOrder::class);

        Order::place($this->event, self::at('2026-07-10 15:00'), [new OrderedItem($this->sticker->sellable(null), 0)], []);
    }

    public function testDiscountsCannotExceedSubtotal(): void
    {
        $this->expectException(InvalidOrder::class);

        Order::place($this->event, self::at('2026-07-10 15:00'), [new OrderedItem($this->sticker->sellable(null), 1)], [new AppliedDiscount('Trop', Money::cents(500))]);
    }

    public function testSumUpImportKeepsTransactionCodeAndSumUpDiscount(): void
    {
        $order = Order::importFromSumUp('TX123', $this->event, self::at('2026-07-10 15:00'), [new OrderedItem($this->sticker->sellable(null), 3)], Money::cents(1_000));

        self::assertSame('TX123', $order->reference());
        self::assertSame('TX123', $order->sumUpTransactionCode());
        self::assertSame(OrderSource::SumUp, $order->source());
        self::assertEquals([new AppliedDiscount('Remise SumUp', Money::cents(200))], $order->appliedDiscounts());
        self::assertSame(1_000, $order->total()->amount());
    }

    public function testSumUpImportKeepsTheMatchingRuleDiscountDespiteSumUpRounding(): void
    {
        $rule = new AppliedDiscount('3 stickers pour 10 €', Money::cents(200), new Ulid());

        $exact = Order::importFromSumUp('TX125', $this->event, self::at('2026-07-10 15:00'), [new OrderedItem($this->sticker->sellable(null), 3)], Money::cents(1_000), ruleDiscounts: [$rule]);
        $rounded = Order::importFromSumUp('TX126', $this->event, self::at('2026-07-10 15:00'), [new OrderedItem($this->sticker->sellable(null), 3)], Money::cents(1_002), ruleDiscounts: [$rule]);

        self::assertEquals([$rule], $exact->appliedDiscounts());
        self::assertEquals([$rule], $rounded->appliedDiscounts());
        self::assertSame(1_200, $rounded->subtotal()->amount());
        self::assertSame(1_000, $rounded->total()->amount());
    }

    public function testSumUpImportFallsBackToSumUpDiscountWhenNoRuleMatches(): void
    {
        $rule = new AppliedDiscount('3 stickers pour 10 €', Money::cents(200), new Ulid());

        $order = Order::importFromSumUp('TX127', $this->event, self::at('2026-07-10 15:00'), [new OrderedItem($this->sticker->sellable(null), 3)], Money::cents(1_003), ruleDiscounts: [$rule]);

        self::assertEquals([new AppliedDiscount('Remise SumUp', Money::cents(197))], $order->appliedDiscounts());
    }

    public function testSumUpImportWithoutDiscount(): void
    {
        $order = Order::importFromSumUp('TX124', $this->event, self::at('2026-07-10 15:00'), [new OrderedItem($this->sticker->sellable(null), 1)], Money::cents(400), PaymentMethod::Cash);

        self::assertSame([], $order->appliedDiscounts());
        self::assertSame(PaymentMethod::Cash, $order->paymentMethod());
    }

    private static function at(string $localTime): \DateTimeImmutable
    {
        return new \DateTimeImmutable($localTime, new \DateTimeZone('Europe/Paris'));
    }

    public function testMovedSalesKeepTheirPricesAndMergeWithTheSameItemAtTheSamePrices(): void
    {
        $tee = Product::create(TestWorkspace::get(), 'TEE', 'Tee', Money::cents(2_000), Money::cents(900), ['M']);
        $order = Order::place($this->event, new \DateTimeImmutable('2026-07-10 12:00'), [
            new OrderedItem($this->tshirt->sellable('M'), 1),
            new OrderedItem($tee->sellable('M'), 2),
            new OrderedItem($this->tshirt->sellable('S'), 1),
        ], []);

        $order->moveSales($this->tshirt->id(), 'M', $tee->sellable('M'));

        self::assertEqualsCanonicalizing(['Tee — M' => 3, 'T-shirt — S' => 1], array_combine(
            array_map(static fn ($line): string => $line->label(), $order->lines()),
            array_map(static fn ($line): int => $line->quantity(), $order->lines()),
        ));
        self::assertSame(8_000, $order->total()->amount());
    }

    public function testMovedSalesAtAnotherPriceStayOnTheirOwnLine(): void
    {
        $order = Order::place($this->event, new \DateTimeImmutable('2026-07-10 12:00'), [new OrderedItem($this->sticker->sellable(null), 2)], []);
        $cheaper = Product::create(TestWorkspace::get(), 'MUG', 'Mug', Money::cents(300), Money::cents(80));
        $order2 = Order::place($this->event, new \DateTimeImmutable('2026-07-10 12:00'), [new OrderedItem($cheaper->sellable(null), 1), new OrderedItem($this->sticker->sellable(null), 1)], []);

        $order->moveSales($this->sticker->id(), null, $cheaper->sellable(null));
        $order2->moveSales($this->sticker->id(), null, $cheaper->sellable(null));

        self::assertSame(['Mug'], array_map(static fn ($line): string => $line->label(), $order->lines()));
        self::assertSame(800, $order->total()->amount());
        self::assertCount(2, $order2->lines());
    }
}
