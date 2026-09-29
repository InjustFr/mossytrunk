<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Order;

use App\Application\Event\ListEvents\ListEventsHandler;
use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Application\Order\DeleteAllOrders\DeleteAllOrdersHandler;
use App\Application\Order\ListOrders\ListOrdersHandler;
use App\Application\Order\PlaceOrder\PlaceOrder;
use App\Application\Order\PlaceOrder\PlaceOrderHandler;
use App\Application\Order\RequestedLine;
use App\Application\Product\CreateProduct\CreateProduct;
use App\Application\Product\CreateProduct\CreateProductHandler;
use App\Application\Product\ListProducts\ListProductsHandler;
use App\Tests\Support\ActsAsUser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeleteAllOrdersTest extends KernelTestCase
{
    use ActsAsUser;

    public function testEveryOrderOfTheWorkspaceGoesButNotItsProductsNorEvents(): void
    {
        self::actAsMemberOf('Atelier B');
        $this->placeOrders(1);
        self::actAsMemberOf('Atelier A');
        $this->placeOrders(2);

        self::assertSame(2, self::getContainer()->get(DeleteAllOrdersHandler::class)());

        self::assertSame([], self::getContainer()->get(ListOrdersHandler::class)());
        self::assertCount(1, self::getContainer()->get(ListProductsHandler::class)());
        self::assertCount(1, self::getContainer()->get(ListEventsHandler::class)());
        self::actAsMemberOf('Atelier B');
        self::assertCount(1, self::getContainer()->get(ListOrdersHandler::class)());
    }

    private function placeOrders(int $count): void
    {
        $container = self::getContainer();
        $sticker = (string) $container->get(CreateProductHandler::class)(new CreateProduct('Sticker', 400));
        $container->get(ScheduleEventHandler::class)(new ScheduleEvent('Salon', 'Lyon', new \DateTimeImmutable('2030-03-14'), new \DateTimeImmutable('2030-03-14')));
        for ($i = 0; $i < $count; ++$i) {
            $container->get(PlaceOrderHandler::class)(new PlaceOrder(new \DateTimeImmutable('2030-03-14 12:00'), [new RequestedLine($sticker, null, 1)]));
        }
    }
}
