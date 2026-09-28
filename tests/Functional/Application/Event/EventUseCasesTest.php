<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Event;

use App\Application\Event\AddExpense\AddExpense;
use App\Application\Event\AddExpense\AddExpenseHandler;
use App\Application\Event\GetEvent\GetEventHandler;
use App\Application\Event\ListEvents\ListEventsHandler;
use App\Application\Event\RemoveExpense\RemoveExpense;
use App\Application\Event\RemoveExpense\RemoveExpenseHandler;
use App\Application\Event\ReviseExpense\ReviseExpense;
use App\Application\Event\ReviseExpense\ReviseExpenseHandler;
use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Application\Event\UpdateEvent\UpdateEvent;
use App\Application\Event\UpdateEvent\UpdateEventHandler;
use App\Domain\Event\EventRepository;
use App\Domain\Event\InvalidEvent;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class EventUseCasesTest extends KernelTestCase
{
    public function testScheduleThenListEvents(): void
    {
        $this->schedule('Japan Expo', '2026-07-09', '2026-07-12');
        $this->schedule('Marché de Noël', '2026-12-05', '2026-12-06');

        $events = self::getContainer()->get(ListEventsHandler::class)();

        self::assertSame(['Marché de Noël', 'Japan Expo'], array_column($events, 'name'));
        self::assertSame('2026-07-09', $events[1]->startDate);
    }

    public function testEventsCannotOverlap(): void
    {
        $this->schedule('Japan Expo', '2026-07-09', '2026-07-12');

        $this->expectExceptionObject(InvalidEvent::overlaps('Japan Expo'));
        $this->schedule('Autre convention', '2026-07-12', '2026-07-13');
    }

    public function testRescheduleIgnoresItselfButNotOthers(): void
    {
        $id = $this->schedule('Japan Expo', '2026-07-09', '2026-07-12');
        $this->schedule('Marché de Noël', '2026-12-05', '2026-12-06');
        $update = self::getContainer()->get(UpdateEventHandler::class);

        $update(new UpdateEvent((string) $id, 'Japan Expo 2026', 'Paris Nord', new \DateTimeImmutable('2026-07-08'), new \DateTimeImmutable('2026-07-12')));
        $event = self::getContainer()->get(GetEventHandler::class)((string) $id);
        self::assertSame('Japan Expo 2026', $event->name);
        self::assertSame('2026-07-08', $event->startDate);

        $this->expectException(InvalidEvent::class);
        $update(new UpdateEvent((string) $id, 'Japan Expo', 'Paris', new \DateTimeImmutable('2026-12-01'), new \DateTimeImmutable('2026-12-05')));
    }

    public function testFindCoveringEvent(): void
    {
        $this->schedule('Japan Expo', '2026-07-09', '2026-07-12');
        $events = self::getContainer()->get(EventRepository::class);

        self::assertSame('Japan Expo', $events->findCovering(new \DateTimeImmutable('2026-07-12T21:59:00+00:00'))?->name());
        self::assertNull($events->findCovering(new \DateTimeImmutable('2026-07-12T22:30:00+00:00')));
    }

    public function testAddAndRemoveExpenses(): void
    {
        $id = (string) $this->schedule('Japan Expo', '2026-07-09', '2026-07-12');
        $expenseId = self::getContainer()->get(AddExpenseHandler::class)(new AddExpense($id, 'Stand', 30_000));
        self::getContainer()->get(AddExpenseHandler::class)(new AddExpense($id, 'Train', 8_950));
        self::getContainer()->get('doctrine')->getManager()->clear();

        $event = self::getContainer()->get(GetEventHandler::class)($id);
        self::assertSame(38_950, $event->expensesTotal);
        self::assertSame(['Stand', 'Train'], array_column($event->expenses, 'label'));

        self::getContainer()->get(ReviseExpenseHandler::class)(new ReviseExpense($id, (string) $expenseId, 'Stand + électricité', 31_500));
        self::getContainer()->get('doctrine')->getManager()->clear();
        self::assertSame(40_450, self::getContainer()->get(GetEventHandler::class)($id)->expensesTotal);

        self::getContainer()->get(RemoveExpenseHandler::class)(new RemoveExpense($id, (string) $expenseId));
        self::getContainer()->get('doctrine')->getManager()->clear();
        self::assertSame(8_950, self::getContainer()->get(GetEventHandler::class)($id)->expensesTotal);
    }

    private function schedule(string $name, string $start, string $end): \Symfony\Component\Uid\Ulid
    {
        return self::getContainer()->get(ScheduleEventHandler::class)(
            new ScheduleEvent($name, 'Paris', new \DateTimeImmutable($start), new \DateTimeImmutable($end)),
        );
    }
}
