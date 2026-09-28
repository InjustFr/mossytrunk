<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Identity;

use App\Application\Discount\CreateDiscountRule\CreateDiscountRuleHandler;
use App\Application\Discount\DiscountRuleDefinition;
use App\Application\Discount\ListDiscountRules\ListDiscountRulesHandler;
use App\Application\Event\GetEvent\GetEventHandler;
use App\Application\Event\ListEvents\ListEventsHandler;
use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Application\Order\GetOrder\GetOrderHandler;
use App\Application\Order\ListOrders\ListOrdersHandler;
use App\Application\Order\PlaceOrder\PlaceOrder;
use App\Application\Order\PlaceOrder\PlaceOrderHandler;
use App\Application\Order\RequestedLine;
use App\Application\Product\CreateProduct\CreateProduct;
use App\Application\Product\CreateProduct\CreateProductHandler;
use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Application\Product\ListProducts\ListProductsHandler;
use App\Application\Product\ListProductTypes\ListProductTypesHandler;
use App\Domain\Shared\NotFound;
use App\Tests\Support\ActsAsUser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class WorkspaceIsolationTest extends KernelTestCase
{
    use ActsAsUser;

    private string $eventId;

    private string $productId;

    private string $orderId;

    protected function setUp(): void
    {
        self::actAsMemberOf('Atelier A');
        $type = self::getContainer()->get(CreateProductTypeHandler::class)('Print');
        $this->productId = (string) self::getContainer()->get(CreateProductHandler::class)(new CreateProduct('Forêt', 1_500, typeId: (string) $type->id()));
        $this->eventId = (string) self::getContainer()->get(ScheduleEventHandler::class)(new ScheduleEvent('Salon', 'Lyon', new \DateTimeImmutable('2030-03-14'), new \DateTimeImmutable('2030-03-15')));
        self::getContainer()->get(CreateDiscountRuleHandler::class)(new DiscountRuleDefinition('2 prints', [$this->productId], 2, 2_500));
        $this->orderId = (string) self::getContainer()->get(PlaceOrderHandler::class)(new PlaceOrder(new \DateTimeImmutable('2030-03-14 15:00'), [new RequestedLine($this->productId, null, 1)]))->id();
    }

    public function testAnotherWorkspaceSeesNothing(): void
    {
        self::actAsMemberOf('Atelier B');

        self::assertSame([], self::getContainer()->get(ListProductsHandler::class)());
        self::assertSame([], self::getContainer()->get(ListProductTypesHandler::class)());
        self::assertSame([], self::getContainer()->get(ListEventsHandler::class)());
        self::assertSame([], self::getContainer()->get(ListOrdersHandler::class)());
        self::assertSame([], self::getContainer()->get(ListDiscountRulesHandler::class)());
    }

    public function testAnotherWorkspaceCannotReachDataById(): void
    {
        self::actAsMemberOf('Atelier B');

        $this->assertNotFound(fn () => self::getContainer()->get(GetEventHandler::class)($this->eventId));
        $this->assertNotFound(fn () => self::getContainer()->get(GetOrderHandler::class)($this->orderId));
        $this->assertNotFound(fn () => self::getContainer()->get(CreateDiscountRuleHandler::class)(new DiscountRuleDefinition('Vol', [$this->productId], 2, 100)));
    }

    public function testNamesAndDatesAreOnlyUniqueWithinAWorkspace(): void
    {
        self::actAsMemberOf('Atelier B');

        self::getContainer()->get(CreateProductTypeHandler::class)('Print');
        self::getContainer()->get(CreateProductHandler::class)(new CreateProduct('Forêt', 1_500));
        self::getContainer()->get(ScheduleEventHandler::class)(new ScheduleEvent('Salon', 'Lyon', new \DateTimeImmutable('2030-03-14'), new \DateTimeImmutable('2030-03-15')));

        self::assertCount(1, self::getContainer()->get(ListProductTypesHandler::class)());
        self::assertCount(1, self::getContainer()->get(ListEventsHandler::class)());
    }

    private function assertNotFound(callable $call): void
    {
        try {
            $call();
            self::fail('Expected NotFound.');
        } catch (NotFound) {
            $this->addToAssertionCount(1);
        }
    }
}
