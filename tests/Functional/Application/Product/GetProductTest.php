<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Product;

use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Application\Order\PlaceOrder\PlaceOrder;
use App\Application\Order\PlaceOrder\PlaceOrderHandler;
use App\Application\Order\RequestedLine;
use App\Application\Product\GetProduct\GetProductHandler;
use App\Application\Product\UpdateProduct\UpdateProduct;
use App\Application\Product\UpdateProduct\UpdateProductHandler;
use App\Application\Stock\Restock\Restock;
use App\Application\Stock\Restock\RestockHandler;
use App\Application\Stock\TakeStockCheck\CountedItem;
use App\Application\Stock\TakeStockCheck\TakeStockCheck;
use App\Application\Stock\TakeStockCheck\TakeStockCheckHandler;
use App\Tests\Support\ActsAsUser;
use App\Tests\Support\CreatesProducts;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class GetProductTest extends KernelTestCase
{
    use ActsAsUser;
    use CreatesProducts;

    public function testProductPageGathersStockMovementsAndPriceHistory(): void
    {
        self::actAsMemberOf();
        $productId = self::createProduct('Sticker', 400);
        $eventId = (string) self::getContainer()->get(ScheduleEventHandler::class)(new ScheduleEvent('Japan Expo', 'Villepinte', new \DateTimeImmutable('2026-07-09'), new \DateTimeImmutable('2026-07-12')));
        self::getContainer()->get(RestockHandler::class)(new Restock($productId, null, 10, 1_000));
        self::getContainer()->get(PlaceOrderHandler::class)(new PlaceOrder(new \DateTimeImmutable('2026-07-10 15:00', new \DateTimeZone('Europe/Paris')), [new RequestedLine($productId, null, 3)]));
        self::getContainer()->get(TakeStockCheckHandler::class)(new TakeStockCheck($eventId, [new CountedItem($productId, null, 6)]));
        self::getContainer()->get(UpdateProductHandler::class)(new UpdateProduct($productId, 'Sticker', 450, []));
        self::getContainer()->get('doctrine')->getManager()->clear();

        $view = self::getContainer()->get(GetProductHandler::class)($productId);

        self::assertSame(450, $view->product->sellingPrice);
        self::assertSame(100, $view->product->buyingPrice);
        self::assertSame(6, $view->product->onHand);
        self::assertSame(3, $view->unitsSoldEver);
        self::assertSame([450, 400], array_column($view->priceHistory, 'price'));
        self::assertEqualsCanonicalizing(['purchase', 'sale', 'loss'], array_column($view->movements, 'kind'));
        self::assertSame(6, array_sum(array_column($view->movements, 'quantity')));
        self::assertNull($view->design);
    }
}
