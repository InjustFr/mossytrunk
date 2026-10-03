<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Order;

use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Application\Order\CheckOrder\CheckOrderHandler;
use App\Application\Order\ListOrdersToCheck\ListOrdersToCheckHandler;
use App\Application\Order\ListOrdersToCheck\OrderToCheckView;
use App\Application\Order\PlaceOrder\PlaceOrder;
use App\Application\Order\PlaceOrder\PlaceOrderHandler;
use App\Application\Order\RefundOrder\RefundOrderHandler;
use App\Application\Order\RequestedLine;
use App\Application\Order\UncheckOrder\UncheckOrderHandler;
use App\Domain\Shared\Exception\NotFound;
use App\Tests\Support\ActsAsUser;
use App\Tests\Support\CreatesProducts;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class OrderCheckTest extends KernelTestCase
{
    use ActsAsUser;
    use CreatesProducts;

    private string $eventId;
    private string $sticker;
    private string $tshirt;

    protected function setUp(): void
    {
        self::actAsMemberOf();
        $this->eventId = (string) self::getContainer()->get(ScheduleEventHandler::class)(
            new ScheduleEvent('Japan Expo', 'Villepinte', new \DateTimeImmutable('2026-07-09'), new \DateTimeImmutable('2026-07-12')),
        );
        $this->sticker = self::createProduct('Sticker', 400);
        $this->tshirt = self::createProduct('T-shirt', 2_000, variants: ['S', 'M']);
    }

    public function testTheEventOrdersAreListedOldestFirstWithTheirItems(): void
    {
        $later = $this->place('2026-07-10 16:00', new RequestedLine($this->sticker, null, 2));
        $earlier = $this->place('2026-07-10 10:00', new RequestedLine($this->tshirt, 'M', 1), new RequestedLine($this->sticker, null, 1));
        self::getContainer()->get(RefundOrderHandler::class)($later);

        $orders = $this->orders();

        self::assertSame([$earlier, $later], array_map(static fn (OrderToCheckView $order): string => $order->id, $orders));
        self::assertSame([['label' => 'T-shirt — M', 'quantity' => 1, 'unidentified' => false], ['label' => 'Sticker', 'quantity' => 1, 'unidentified' => false]], $orders[0]->lines);
        self::assertSame(2_400, $orders[0]->total);
        self::assertSame('2026-07-10T10:00:00+02:00', $orders[0]->placedAt);
        self::assertFalse($orders[0]->refunded);
        self::assertTrue($orders[1]->refunded);
        self::assertFalse($orders[0]->checked);
    }

    public function testAnOrderIsTickedOffAndUnticked(): void
    {
        $order = $this->place('2026-07-10 10:00', new RequestedLine($this->sticker, null, 1));

        self::getContainer()->get(CheckOrderHandler::class)($order);
        self::assertTrue($this->orders()[0]->checked);

        self::getContainer()->get(UncheckOrderHandler::class)($order);
        self::assertFalse($this->orders()[0]->checked);
    }

    public function testAnotherWorkspaceCannotListTheOrders(): void
    {
        self::actAsMemberOf('Autre atelier');

        $this->expectException(NotFound::class);
        $this->orders();
    }

    public function testAnotherWorkspaceCannotTickAnOrder(): void
    {
        $order = $this->place('2026-07-10 10:00', new RequestedLine($this->sticker, null, 1));
        self::actAsMemberOf('Autre atelier');

        $this->expectException(NotFound::class);
        self::getContainer()->get(CheckOrderHandler::class)($order);
    }

    /**
     * @return list<OrderToCheckView>
     */
    private function orders(): array
    {
        self::getContainer()->get('doctrine')->getManager()->clear();

        return self::getContainer()->get(ListOrdersToCheckHandler::class)($this->eventId);
    }

    private function place(string $placedAt, RequestedLine ...$lines): string
    {
        return (string) self::getContainer()->get(PlaceOrderHandler::class)(new PlaceOrder(new \DateTimeImmutable($placedAt, new \DateTimeZone('Europe/Paris')), array_values($lines)))->id();
    }
}
