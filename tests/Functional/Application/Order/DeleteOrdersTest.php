<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Order;

use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Application\Order\DeleteOrders\DeleteOrders;
use App\Application\Order\DeleteOrders\DeleteOrdersHandler;
use App\Application\Order\ListOrders\ListOrdersHandler;
use App\Application\Order\PlaceOrder\PlaceOrder;
use App\Application\Order\PlaceOrder\PlaceOrderHandler;
use App\Application\Order\RequestedLine;
use App\Application\Product\ListProducts\ListProductsHandler;
use App\Application\Stock\Restock\Restock;
use App\Application\Stock\Restock\RestockHandler;
use App\Domain\Shared\Exception\NotFound;
use App\Tests\Support\ActsAsUser;
use App\Tests\Support\CreatesProducts;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeleteOrdersTest extends KernelTestCase
{
    use ActsAsUser;
    use CreatesProducts;

    public function testTheSelectedOrdersGoAndPutTheirUnitsBackInStock(): void
    {
        self::actAsMemberOf();
        [$first, $second, $kept] = $this->placeOrders(3);

        self::assertSame(2, $this->delete([$first, $second, $first]));

        self::assertSame([$kept], array_column(self::getContainer()->get(ListOrdersHandler::class)(), 'id'));
        self::assertSame(9, self::getContainer()->get(ListProductsHandler::class)()[0]->onHand);
    }

    public function testAnOrderOfAnotherWorkspaceAbortsTheDeletion(): void
    {
        self::actAsMemberOf('Atelier B');
        [$other] = $this->placeOrders(1);
        self::actAsMemberOf('Atelier A');
        [$mine] = $this->placeOrders(1);

        try {
            $this->delete([$mine, $other]);
            self::fail('an order of another workspace was deleted');
        } catch (NotFound) {
        }

        self::getContainer()->get('doctrine')->getManager()->clear();
        self::assertCount(1, self::getContainer()->get(ListOrdersHandler::class)());
    }

    /**
     * @param list<string> $orderIds
     */
    private function delete(array $orderIds): int
    {
        $deleted = self::getContainer()->get(DeleteOrdersHandler::class)(new DeleteOrders($orderIds));
        self::getContainer()->get('doctrine')->getManager()->clear();

        return $deleted;
    }

    /**
     * @return list<string>
     */
    private function placeOrders(int $count): array
    {
        $container = self::getContainer();
        $sticker = (string) self::createProduct('Sticker', 400);
        $container->get(RestockHandler::class)(new Restock($sticker, null, 10, 1_000));
        $container->get(ScheduleEventHandler::class)(new ScheduleEvent('Salon', 'Lyon', new \DateTimeImmutable('2030-03-14'), new \DateTimeImmutable('2030-03-14')));
        $ids = [];
        for ($i = 0; $i < $count; ++$i) {
            $ids[] = (string) $container->get(PlaceOrderHandler::class)(new PlaceOrder(new \DateTimeImmutable('2030-03-14 12:00'), [new RequestedLine($sticker, null, 1)]))->id();
        }

        return $ids;
    }
}
