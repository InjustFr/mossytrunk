<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Event;

use App\Application\Event\AddExpense\AddExpense;
use App\Application\Event\AddExpense\AddExpenseHandler;
use App\Application\Event\ListEvents\ListEventsHandler;
use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Application\Order\PlaceOrder\PlaceOrder;
use App\Application\Order\PlaceOrder\PlaceOrderHandler;
use App\Application\Order\RequestedLine;
use App\Application\Product\CreateProduct\CreateProduct;
use App\Application\Product\CreateProduct\CreateProductHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

final class ListEventsTest extends KernelTestCase
{
    use ClockSensitiveTrait;

    public function testEachEventShowsItsTurnoverAndResult(): void
    {
        $container = self::getContainer();
        $expo = (string) $container->get(ScheduleEventHandler::class)(new ScheduleEvent('Japan Expo', 'Villepinte', new \DateTimeImmutable('2026-07-09'), new \DateTimeImmutable('2026-07-12')));
        $container->get(ScheduleEventHandler::class)(new ScheduleEvent('Marché', 'Lyon', new \DateTimeImmutable('2026-08-01'), new \DateTimeImmutable('2026-08-01')));
        $container->get(AddExpenseHandler::class)(new AddExpense($expo, 'Stand', 10_000));
        $print = (string) $container->get(CreateProductHandler::class)(new CreateProduct('Print', 1_500, 500));
        $container->get(PlaceOrderHandler::class)(new PlaceOrder(new \DateTimeImmutable('2026-07-10 12:00'), [new RequestedLine($print, null, 10)]));
        $container->get('doctrine')->getManager()->clear();

        $events = array_column($container->get(ListEventsHandler::class)(), null, 'name');

        self::assertSame(1, $events['Japan Expo']->orderCount);
        self::assertSame(15_000, $events['Japan Expo']->turnover);
        self::assertSame(15_000 - 5_000 - 10_000 - 1_920, $events['Japan Expo']->result);
        self::assertSame(0, $events['Marché']->turnover);
        self::assertSame(0, $events['Marché']->result);
    }

    public function testEventsAreUpcomingOngoingOrPastRelativeToToday(): void
    {
        $container = self::getContainer();
        foreach ([['Passé', '2026-07-01'], ['En cours', '2026-07-10'], ['À venir', '2026-07-20']] as [$name, $day]) {
            $container->get(ScheduleEventHandler::class)(new ScheduleEvent($name, 'Lyon', new \DateTimeImmutable($day), new \DateTimeImmutable($day)));
        }

        self::mockTime('2026-07-10 15:00');
        $timings = array_column($container->get(ListEventsHandler::class)(), 'timing', 'name');

        self::assertSame(['À venir' => 'upcoming', 'En cours' => 'ongoing', 'Passé' => 'past'], $timings);
    }
}
