<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Product;

use App\Application\Discount\CreateDiscountRule\CreateDiscountRuleHandler;
use App\Application\Discount\ListDiscountRules\ListDiscountRulesHandler;
use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Application\Order\GetOrder\GetOrderHandler;
use App\Application\Order\PlaceOrder\PlaceOrder;
use App\Application\Order\PlaceOrder\PlaceOrderHandler;
use App\Application\Order\RequestedLine;
use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Application\Product\DeleteAllProducts\DeleteAllProductsHandler;
use App\Application\Product\ListProducts\ListProductsHandler;
use App\Application\Product\ListProductTypes\ListProductTypesHandler;
use App\Tests\Support\ActsAsUser;
use App\Tests\Support\CreatesProducts;
use App\Tests\Support\DiscountRules;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeleteAllProductsTest extends KernelTestCase
{
    use ActsAsUser;
    use CreatesProducts;

    public function testTheCatalogueEmptiesDiscountsListingOnlyProductsGoAndPastOrdersStay(): void
    {
        $container = self::getContainer();
        self::actAsMemberOf('Atelier B');
        self::createProduct('Mug', 1_200);
        self::actAsMemberOf('Atelier A');
        $print = (string) $container->get(CreateProductTypeHandler::class)('Print')->id();
        $sticker = (string) self::createProduct('Sticker', 400);
        $pin = (string) self::createProduct('Pin', 400);
        $container->get(CreateDiscountRuleHandler::class)(DiscountRules::fixedPrice('3 pour 10', 1_000, DiscountRules::product($sticker, 2), DiscountRules::product($pin, 1)));
        $container->get(CreateDiscountRuleHandler::class)(DiscountRules::fixedPrice('Prints et pins', 2_000, DiscountRules::product($pin, 1), DiscountRules::type($print, 1)));
        $container->get(ScheduleEventHandler::class)(new ScheduleEvent('Salon', 'Lyon', new \DateTimeImmutable('2030-03-14'), new \DateTimeImmutable('2030-03-14')));
        $order = (string) $container->get(PlaceOrderHandler::class)(new PlaceOrder(new \DateTimeImmutable('2030-03-14 12:00'), [new RequestedLine($pin, null, 2)]))->id();

        self::assertSame(2, $container->get(DeleteAllProductsHandler::class)());
        $container->get('doctrine')->getManager()->clear();

        self::assertCount(0, $container->get(ListProductsHandler::class)());
        self::assertCount(2, $container->get(ListProductTypesHandler::class)(), 'Print and the miscellaneous type stay');
        $rules = $container->get(ListDiscountRulesHandler::class)();
        self::assertSame(['Prints et pins'], array_column($rules, 'name'));
        self::assertSame(['Print'], array_column($rules[0]->conditions, 'name'));
        self::assertSame('Pin', $container->get(GetOrderHandler::class)($order)->lines[0]['label']);
        self::actAsMemberOf('Atelier B');
        self::assertCount(1, $container->get(ListProductsHandler::class)());
    }
}
