<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Dashboard;

use App\Application\Dashboard\GetDashboard\GetDashboardHandler;
use App\Application\Event\AddExpense\AddExpense;
use App\Application\Event\AddExpense\AddExpenseHandler;
use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Application\Order\PlaceOrder\PlaceOrder;
use App\Application\Order\PlaceOrder\PlaceOrderHandler;
use App\Application\Order\RequestedLine;
use App\Application\Product\CreateProduct\CreateProduct;
use App\Application\Product\CreateProduct\CreateProductHandler;
use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Tests\Support\ActsAsUser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class GetDashboardTest extends KernelTestCase
{
    use ActsAsUser;

    protected function setUp(): void
    {
        self::actAsMemberOf();
    }

    public function testMonthlyAndYearlyResults(): void
    {
        $container = self::getContainer();
        $expo = (string) $container->get(ScheduleEventHandler::class)(new ScheduleEvent('Japan Expo', 'Villepinte', new \DateTimeImmutable('2026-07-09'), new \DateTimeImmutable('2026-07-12')));
        $container->get(ScheduleEventHandler::class)(new ScheduleEvent('Marché', 'Lyon', new \DateTimeImmutable('2025-12-06'), new \DateTimeImmutable('2025-12-06')));
        $container->get(AddExpenseHandler::class)(new AddExpense($expo, 'Stand', 10_000));
        $print = (string) $container->get(CreateProductHandler::class)(new CreateProduct('Print', 1_500, 500));
        $place = $container->get(PlaceOrderHandler::class);
        $place(new PlaceOrder(new \DateTimeImmutable('2026-07-10 12:00'), [new RequestedLine($print, null, 10)]));
        $place(new PlaceOrder(new \DateTimeImmutable('2025-12-06 12:00'), [new RequestedLine($print, null, 2)]));
        $container->get('doctrine')->getManager()->clear();

        $dashboard = $container->get(GetDashboardHandler::class)(2026);

        self::assertSame(2026, $dashboard->year);
        self::assertSame([2026, 2025], $dashboard->years);
        self::assertCount(12, $dashboard->months);
        self::assertSame(15_000, $dashboard->months[6]['turnover']);
        self::assertSame(10_000, $dashboard->months[6]['expenses']);
        self::assertSame(0, $dashboard->months[0]['turnover']);
        self::assertSame(15_000 - 5_000 - 10_000 - 1_920, $dashboard->total['result']);
        self::assertSame([2026, 2025], array_column($dashboard->byYear, 'year'));
        self::assertSame(3_000, $dashboard->byYear[1]['turnover']);
        self::assertSame(['Japan Expo'], array_column($dashboard->events, 'name'));
        self::assertSame($dashboard->total['result'], $dashboard->events[0]['result']);
        self::assertSame([['id' => $print, 'name' => 'Print', 'typeName' => null, 'quantity' => 10, 'sales' => 15_000]], $dashboard->products);
        self::assertSame([['name' => null, 'quantity' => 10, 'sales' => 15_000]], $dashboard->types);
    }

    public function testEventsAreRankedByResultAndSalesGroupedByType(): void
    {
        $container = self::getContainer();
        $schedule = $container->get(ScheduleEventHandler::class);
        $good = (string) $schedule(new ScheduleEvent('Bon salon', 'Lyon', new \DateTimeImmutable('2027-03-06'), new \DateTimeImmutable('2027-03-06')));
        $bad = (string) $schedule(new ScheduleEvent('Salon raté', 'Lille', new \DateTimeImmutable('2027-04-10'), new \DateTimeImmutable('2027-04-10')));
        $container->get(AddExpenseHandler::class)(new AddExpense($bad, 'Stand', 5_000));
        $prints = (string) $container->get(CreateProductTypeHandler::class)('Print')->id();
        $print = (string) $container->get(CreateProductHandler::class)(new CreateProduct('Forêt', 1_500, 300, [], $prints));
        $sticker = (string) $container->get(CreateProductHandler::class)(new CreateProduct('Sticker', 400));
        $place = $container->get(PlaceOrderHandler::class);
        $place(new PlaceOrder(new \DateTimeImmutable('2027-03-06 12:00'), [new RequestedLine($print, null, 2), new RequestedLine($sticker, null, 1)]));
        $container->get('doctrine')->getManager()->clear();

        $dashboard = $container->get(GetDashboardHandler::class)(2027);

        self::assertSame([$good, $bad], array_column($dashboard->events, 'id'));
        self::assertSame(['Print Forêt', 'Sticker'], array_column($dashboard->products, 'name'));
        self::assertSame([['name' => 'Print', 'quantity' => 2, 'sales' => 3_000], ['name' => null, 'quantity' => 1, 'sales' => 400]], $dashboard->types);
        self::assertSame(1, $dashboard->productsWithoutCost);
    }

    public function testYearWithoutDataIsStillSelectable(): void
    {
        $dashboard = self::getContainer()->get(GetDashboardHandler::class)(2031);

        self::assertSame([2031], $dashboard->years);
        self::assertSame(0, $dashboard->total['turnover']);
        self::assertSame([], $dashboard->byYear);
        self::assertSame([], $dashboard->events);
        self::assertSame([], $dashboard->products);
    }
}
