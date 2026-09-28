<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Order;

use App\Application\Discount\CreateDiscountRule\CreateDiscountRuleHandler;
use App\Application\Discount\DiscountRuleDefinition;
use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Application\Event\UpdateEvent\UpdateEvent;
use App\Application\Event\UpdateEvent\UpdateEventHandler;
use App\Application\Order\DeleteOrder\DeleteOrderHandler;
use App\Application\Order\GetOrder\GetOrderHandler;
use App\Application\Order\ListOrders\ListOrdersHandler;
use App\Application\Order\PlaceOrder\PlaceOrder;
use App\Application\Order\PlaceOrder\PlaceOrderHandler;
use App\Application\Order\PreviewOrder\PreviewOrderHandler;
use App\Application\Order\RequestedLine;
use App\Application\Product\CreateProduct\CreateProduct;
use App\Application\Product\CreateProduct\CreateProductHandler;
use App\Domain\Event\InvalidEvent;
use App\Domain\Order\InvalidOrder;
use App\Domain\Product\InvalidProduct;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

final class OrderUseCasesTest extends KernelTestCase
{
    private string $eventId;
    private string $sticker;
    private string $tshirt;

    protected function setUp(): void
    {
        $this->eventId = (string) self::getContainer()->get(ScheduleEventHandler::class)(
            new ScheduleEvent('Japan Expo', 'Villepinte', new \DateTimeImmutable('2026-07-09'), new \DateTimeImmutable('2026-07-12')),
        );
        $createProduct = self::getContainer()->get(CreateProductHandler::class);
        $this->sticker = (string) $createProduct(new CreateProduct('Sticker', 400, 80));
        $this->tshirt = (string) $createProduct(new CreateProduct('T-shirt', 2_000, 900, ['S', 'M']));
        self::getContainer()->get(CreateDiscountRuleHandler::class)(new DiscountRuleDefinition('3 stickers pour 10 €', [$this->sticker], 3, 1_000));
    }

    public function testPlaceOrderLinksEventAndAppliesDiscounts(): void
    {
        $order = $this->place('2026-07-10 15:30', [new RequestedLine($this->sticker, null, 3), new RequestedLine($this->tshirt, 'M', 1)]);
        self::getContainer()->get('doctrine')->getManager()->clear();

        $view = self::getContainer()->get(GetOrderHandler::class)((string) $order->id());
        self::assertSame('Japan Expo', $view->event['name']);
        self::assertSame(3_200, $view->subtotal);
        self::assertSame([['label' => '3 stickers pour 10 €', 'amount' => 200]], $view->discounts);
        self::assertSame(3_000, $view->total);
        self::assertSame(1_140, $view->costOfGoods);
        self::assertSame(1_860, $view->margin);
        self::assertSame('2026-07-10T15:30:00+02:00', $view->placedAt);
    }

    public function testOrderOutsideAnyEventIsRejected(): void
    {
        $this->expectExceptionMessage('Aucun événement le 13/07/2026');

        $this->place('2026-07-13 10:00', [new RequestedLine($this->sticker, null, 1)]);
    }

    public function testVariantIsRequiredForProductsWithVariants(): void
    {
        $this->expectException(InvalidProduct::class);

        $this->place('2026-07-10 15:30', [new RequestedLine($this->tshirt, null, 1)]);
    }

    public function testQuantityMustBePositive(): void
    {
        $this->expectException(InvalidOrder::class);

        $this->place('2026-07-10 15:30', [new RequestedLine($this->sticker, null, 0)]);
    }

    public function testPreviewMatchesPlacedOrder(): void
    {
        $preview = self::getContainer()->get(PreviewOrderHandler::class)(
            new \DateTimeImmutable('2026-07-10 15:30'),
            [new RequestedLine($this->sticker, null, 7)],
        );

        self::assertSame('Japan Expo', $preview->event['name']);
        self::assertSame(2_800, $preview->subtotal);
        self::assertSame([['label' => '3 stickers pour 10 € ×2', 'amount' => 400]], $preview->discounts);
        self::assertSame(2_400, $preview->total);

        $noEvent = self::getContainer()->get(PreviewOrderHandler::class)(new \DateTimeImmutable('2026-08-01 12:00'), [new RequestedLine($this->sticker, null, 1)]);
        self::assertNull($noEvent->event);
    }

    public function testListFilterAndDelete(): void
    {
        $order = $this->place('2026-07-10 15:30', [new RequestedLine($this->sticker, null, 1)]);
        $this->place('2026-07-11 11:00', [new RequestedLine($this->sticker, null, 2)]);

        $list = self::getContainer()->get(ListOrdersHandler::class);
        self::assertCount(2, $list($this->eventId));
        self::assertSame(800, $list()[0]->total, 'most recent first');
        self::assertSame([], $list((string) new Ulid()));

        self::getContainer()->get(DeleteOrderHandler::class)((string) $order->id());
        self::assertCount(1, $list());
    }

    public function testEventCannotBeRescheduledAwayFromItsOrders(): void
    {
        $this->place('2026-07-12 18:00', [new RequestedLine($this->sticker, null, 1)]);

        $this->expectException(InvalidEvent::class);
        self::getContainer()->get(UpdateEventHandler::class)(new UpdateEvent($this->eventId, 'Japan Expo', 'Villepinte', new \DateTimeImmutable('2026-07-09'), new \DateTimeImmutable('2026-07-11')));
    }

    /**
     * @param list<RequestedLine> $lines
     */
    private function place(string $localTime, array $lines): \App\Domain\Order\Order
    {
        return self::getContainer()->get(PlaceOrderHandler::class)(
            new PlaceOrder(new \DateTimeImmutable($localTime, new \DateTimeZone('Europe/Paris')), $lines),
        );
    }
}
