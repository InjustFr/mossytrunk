<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Product;

use App\Application\Discount\CreateDiscountRule\CreateDiscountRuleHandler;
use App\Application\Discount\DiscountRuleDefinition;
use App\Application\Discount\ListDiscountRules\ListDiscountRulesHandler;
use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Application\Order\GetOrder\GetOrderHandler;
use App\Application\Order\PlaceOrder\PlaceOrder;
use App\Application\Order\PlaceOrder\PlaceOrderHandler;
use App\Application\Order\RequestedLine;
use App\Application\Product\CreateProduct\CreateProduct;
use App\Application\Product\CreateProduct\CreateProductHandler;
use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Application\Product\DeleteAllProducts\DeleteAllProductsHandler;
use App\Application\Product\ListProducts\ListProductsHandler;
use App\Application\Product\ListProductTypes\ListProductTypesHandler;
use App\Tests\Support\ActsAsUser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeleteAllProductsTest extends KernelTestCase
{
    use ActsAsUser;

    public function testTheCatalogueEmptiesDiscountsListingOnlyProductsGoAndPastOrdersStay(): void
    {
        $container = self::getContainer();
        self::actAsMemberOf('Atelier B');
        $container->get(CreateProductHandler::class)(new CreateProduct('Mug', 1_200));
        self::actAsMemberOf('Atelier A');
        $print = (string) $container->get(CreateProductTypeHandler::class)('Print')->id();
        $sticker = (string) $container->get(CreateProductHandler::class)(new CreateProduct('Sticker', 400));
        $pin = (string) $container->get(CreateProductHandler::class)(new CreateProduct('Pin', 400));
        $container->get(CreateDiscountRuleHandler::class)(new DiscountRuleDefinition('3 pour 10', [$sticker, $pin], 3, 1_000));
        $container->get(CreateDiscountRuleHandler::class)(new DiscountRuleDefinition('Prints et pins', [$pin], 2, 2_000, [$print]));
        $container->get(ScheduleEventHandler::class)(new ScheduleEvent('Salon', 'Lyon', new \DateTimeImmutable('2030-03-14'), new \DateTimeImmutable('2030-03-14')));
        $order = (string) $container->get(PlaceOrderHandler::class)(new PlaceOrder(new \DateTimeImmutable('2030-03-14 12:00'), [new RequestedLine($pin, null, 2)]))->id();

        self::assertSame(2, $container->get(DeleteAllProductsHandler::class)());
        $container->get('doctrine')->getManager()->clear();

        self::assertSame([], $container->get(ListProductsHandler::class)());
        self::assertCount(1, $container->get(ListProductTypesHandler::class)());
        $rules = $container->get(ListDiscountRulesHandler::class)();
        self::assertSame(['Prints et pins'], array_column($rules, 'name'));
        self::assertSame([], $rules[0]->products);
        self::assertSame('Pin', $container->get(GetOrderHandler::class)($order)->lines[0]['label']);
        self::actAsMemberOf('Atelier B');
        self::assertCount(1, $container->get(ListProductsHandler::class)());
    }
}
