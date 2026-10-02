<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Sales;

use App\Domain\Event\Event;
use App\Domain\Order\Exception\NegativePostage;
use App\Domain\Order\Order;
use App\Domain\Order\OrderedItem;
use App\Domain\Product\Product;
use App\Domain\Reporting\EventResult;
use App\Domain\Sales\ChannelCostKind;
use App\Domain\Sales\Exception\EmptyCostLabel;
use App\Domain\Sales\OrderCharge;
use App\Domain\Sales\SalesChannel;
use App\Domain\Shared\DateRange;
use App\Domain\Shared\Exception\NegativeAmount;
use App\Domain\Shared\Money;
use App\Tests\Support\TestProductType;
use App\Tests\Support\TestWorkspace;
use PHPUnit\Framework\TestCase;

final class ChannelCostTest extends TestCase
{
    private ?Event $event = null;

    public function testAChannelChargesAFixedAmountAndAPercentageOfEachOrder(): void
    {
        $etsy = $this->etsy();

        self::assertSame([['label' => 'Transaction', 'amount' => 20], ['label' => 'Commission', 'amount' => 98]], array_map(static fn (OrderCharge $charge): array => $charge->toArray(), $etsy->chargesOn(Money::cents(1_500))));
    }

    public function testAnOrderKeepsTheChargesOfItsChannelAndItsPostage(): void
    {
        $etsy = $this->etsy();
        $order = $this->order($etsy, 1);

        self::assertSame(118, $order->channelCosts()->amount());

        $etsy->removeCost($etsy->costs()[0]->id());
        self::assertSame(118, $order->channelCosts()->amount());
        $order->chargeChannelCosts();
        self::assertSame(98, $order->channelCosts()->amount());

        $order->stamp(Money::cents(196));
        self::assertSame(294, $order->channelCosts()->amount());
    }

    public function testAnAbsorbedOrderAddsItsPostageAndTheChargesFollowTheNewTotal(): void
    {
        $etsy = $this->etsy();
        $order = $this->order($etsy, 1);
        $other = $this->order($etsy, 1);
        $other->stamp(Money::cents(100));

        $order->absorb($other);

        self::assertSame([20, 195, 100], [$order->channelCharges()[0]->amount->amount(), $order->channelCharges()[1]->amount->amount(), $order->postage()->amount()]);
    }

    public function testTheEventResultSubtractsSuppliesAndChannelCosts(): void
    {
        $etsy = $this->etsy();
        $order = $this->order($etsy, 1);
        $order->stamp(Money::cents(200));

        $result = EventResult::of($order->event() ?? self::fail('no event'), [$order]);

        self::assertSame([318, 1_500 - 318 - 192], [$result->channelCosts->amount(), $result->result->amount()]);
    }

    public function testACostNeedsALabelAndNoNegativeAmount(): void
    {
        $this->expectException(EmptyCostLabel::class);
        $this->etsy()->addCost(' ', ChannelCostKind::Fixed, 10);
    }

    public function testACostIsNeverNegative(): void
    {
        $this->expectException(NegativeAmount::class);
        $this->etsy()->addCost('Remise', ChannelCostKind::Fixed, -10);
    }

    public function testPostageIsNeverNegative(): void
    {
        $this->expectException(NegativePostage::class);
        $this->order($this->etsy(), 1)->stamp(Money::cents(-1));
    }

    private function etsy(): SalesChannel
    {
        $etsy = SalesChannel::open(TestWorkspace::get(), 'Etsy');
        $etsy->addCost('Transaction', ChannelCostKind::Fixed, 20);
        $etsy->addCost('Commission', ChannelCostKind::Percent, 650);

        return $etsy;
    }

    private function order(SalesChannel $channel, int $quantity): Order
    {
        $event = $this->event ??= Event::schedule(TestWorkspace::get(), 'Salon', 'Lyon', DateRange::fromDates(new \DateTimeImmutable('2026-07-09'), new \DateTimeImmutable('2026-07-09')));
        $print = Product::create(TestWorkspace::get(), 'PRT', 'Print', Money::cents(1_500), TestProductType::get());

        return Order::place('CMD-1', $event, new \DateTimeImmutable('2026-07-09 14:00', new \DateTimeZone('Europe/Paris')), [new OrderedItem($print->sellable(null), $quantity)], [], $channel);
    }
}
