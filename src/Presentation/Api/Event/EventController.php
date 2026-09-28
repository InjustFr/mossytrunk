<?php

declare(strict_types=1);

namespace App\Presentation\Api\Event;

use App\Application\Event\AddExpense\AddExpense;
use App\Application\Event\AddExpense\AddExpenseHandler;
use App\Application\Event\GetEvent\GetEventHandler;
use App\Application\Event\ListEvents\ListEventsHandler;
use App\Application\Event\RemoveExpense\RemoveExpense;
use App\Application\Event\RemoveExpense\RemoveExpenseHandler;
use App\Application\Event\ScheduleEvent\ScheduleEvent;
use App\Application\Event\ScheduleEvent\ScheduleEventHandler;
use App\Application\Event\UpdateEvent\UpdateEvent;
use App\Application\Event\UpdateEvent\UpdateEventHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/events', format: 'json')]
final class EventController extends AbstractController
{
    #[Route('', name: 'api_events_list', methods: ['GET'])]
    public function list(ListEventsHandler $listEvents): JsonResponse
    {
        return $this->json($listEvents());
    }

    #[Route('', name: 'api_events_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] EventPayload $payload, ScheduleEventHandler $scheduleEvent): JsonResponse
    {
        $id = $scheduleEvent(new ScheduleEvent($payload->name, $payload->location, $payload->start(), $payload->end()));

        return $this->json(['id' => (string) $id], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_events_show', requirements: ['id' => Requirement::ULID], methods: ['GET'])]
    public function show(string $id, GetEventHandler $getEvent): JsonResponse
    {
        return $this->json($getEvent($id));
    }

    #[Route('/{id}', name: 'api_events_update', requirements: ['id' => Requirement::ULID], methods: ['PUT'])]
    public function update(string $id, #[MapRequestPayload] EventPayload $payload, UpdateEventHandler $updateEvent): Response
    {
        $updateEvent(new UpdateEvent($id, $payload->name, $payload->location, $payload->start(), $payload->end()));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/expenses', name: 'api_events_add_expense', requirements: ['id' => Requirement::ULID], methods: ['POST'])]
    public function addExpense(string $id, #[MapRequestPayload] ExpensePayload $payload, AddExpenseHandler $addExpense): JsonResponse
    {
        $expenseId = $addExpense(new AddExpense($id, $payload->label, $payload->amount));

        return $this->json(['id' => (string) $expenseId], Response::HTTP_CREATED);
    }

    #[Route('/{id}/expenses/{expenseId}', name: 'api_events_remove_expense', requirements: ['id' => Requirement::ULID, 'expenseId' => Requirement::ULID], methods: ['DELETE'])]
    public function removeExpense(string $id, string $expenseId, RemoveExpenseHandler $removeExpense): Response
    {
        $removeExpense(new RemoveExpense($id, $expenseId));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
