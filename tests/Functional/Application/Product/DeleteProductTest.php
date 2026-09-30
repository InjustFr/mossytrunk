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
use App\Application\Product\DeleteProduct\DeleteProductHandler;
use App\Application\Product\ListProducts\ListProductsHandler;
use App\Domain\Discount\Exception\OnlyEligibleProduct;
use App\Tests\Support\ActsAsUser;
use App\Tests\Support\CreatesProducts;
use App\Tests\Support\DiscountRules;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeleteProductTest extends KernelTestCase
{
    use ActsAsUser;
    use CreatesProducts;

    protected function setUp(): void
    {
        self::actAsMemberOf();
    }

    public function testDeletedProductLeavesTheCatalogueAndItsDiscountsButNotPastOrders(): void
    {
        $container = self::getContainer();
        $sticker = $this->product('Sticker');
        $pin = $this->product('Pin');
        $container->get(CreateDiscountRuleHandler::class)(DiscountRules::fixedPrice('3 pour 10', 1_000, DiscountRules::product($sticker, 2), DiscountRules::product($pin, 1)));
        $container->get(ScheduleEventHandler::class)(new ScheduleEvent('Salon', 'Lyon', new \DateTimeImmutable('2030-03-14'), new \DateTimeImmutable('2030-03-14')));
        $order = (string) $container->get(PlaceOrderHandler::class)(new PlaceOrder(new \DateTimeImmutable('2030-03-14 12:00'), [new RequestedLine($pin, null, 2)]))->id();

        $container->get(DeleteProductHandler::class)($pin);
        $container->get('doctrine')->getManager()->clear();

        self::assertSame(['Sticker'], array_column($container->get(ListProductsHandler::class)(), 'name'));
        self::assertSame(['Sticker'], array_column($container->get(ListDiscountRulesHandler::class)()[0]->conditions, 'name'));
        $view = $container->get(GetOrderHandler::class)($order);
        self::assertSame('Pin', $view->lines[0]['label']);
        self::assertSame(800, $view->total);
    }

    public function testAProductThatADiscountTargetsAloneIsKept(): void
    {
        $pin = $this->product('Pin');
        self::getContainer()->get(CreateDiscountRuleHandler::class)(DiscountRules::fixedPrice('2 pins', 700, DiscountRules::product($pin, 2)));

        $this->expectExceptionObject(new OnlyEligibleProduct('2 pins', 'Pin'));
        self::getContainer()->get(DeleteProductHandler::class)($pin);
    }

    private function product(string $name): string
    {
        return (string) self::createProduct($name, 400);
    }
}
