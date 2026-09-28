<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Event;

use App\Application\Event\AddExpense\AddExpense;
use App\Application\Event\AddExpense\AddExpenseHandler;
use App\Application\Event\GetEventReport\GetEventReportHandler;
use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Application\Order\PlaceOrder\PlaceOrder;
use App\Application\Order\PlaceOrder\PlaceOrderHandler;
use App\Application\Order\RequestedLine;
use App\Application\Product\CreateProduct\CreateProduct;
use App\Application\Product\CreateProduct\CreateProductHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class EventReportTest extends KernelTestCase
{
    public function testReportOnlyCountsTheEventsOrders(): void
    {
        $container = self::getContainer();
        $expo = (string) $container->get(ScheduleEventHandler::class)(new ScheduleEvent('Japan Expo', 'Villepinte', new \DateTimeImmutable('2026-07-09'), new \DateTimeImmutable('2026-07-12')));
        $container->get(ScheduleEventHandler::class)(new ScheduleEvent('Marché', 'Lyon', new \DateTimeImmutable('2026-08-01'), new \DateTimeImmutable('2026-08-01')));
        $container->get(AddExpenseHandler::class)(new AddExpense($expo, 'Stand', 10_000));
        $print = (string) $container->get(CreateProductHandler::class)(new CreateProduct('Print', 1_500, 500));

        $place = $container->get(PlaceOrderHandler::class);
        $place(new PlaceOrder(new \DateTimeImmutable('2026-07-10 12:00'), [new RequestedLine($print, null, 10)]));
        $place(new PlaceOrder(new \DateTimeImmutable('2026-08-01 12:00'), [new RequestedLine($print, null, 1)]));
        $container->get('doctrine')->getManager()->clear();

        $report = $container->get(GetEventReportHandler::class)($expo);

        self::assertSame(1, $report->orders['count']);
        self::assertSame(15_000, $report->orders['turnover']);
        self::assertSame(5_000, $report->orders['costOfGoods']);
        self::assertSame([['label' => 'Stand', 'amount' => 10_000]], $report->expenses['items']);
        self::assertSame(12.8, $report->urssaf['rate']);
        self::assertSame(1_920, $report->urssaf['amount']);
        self::assertSame(15_000 - 5_000 - 10_000 - 1_920, $report->total['result']);
    }
}
