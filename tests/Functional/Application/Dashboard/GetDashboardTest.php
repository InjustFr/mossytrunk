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
    }

    public function testYearWithoutDataIsStillSelectable(): void
    {
        $dashboard = self::getContainer()->get(GetDashboardHandler::class)(2031);

        self::assertSame([2031], $dashboard->years);
        self::assertSame(0, $dashboard->total['turnover']);
        self::assertSame([], $dashboard->byYear);
    }
}
