<?php

declare(strict_types=1);

namespace App\Presentation\Api\Event;

use App\Application\Event\AddExpense\AddExpense;
use App\Application\Event\AddExpense\AddExpenseHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/events/{id}/expenses', name: 'api_events_add_expense', requirements: ['id' => Requirement::ULID], methods: ['POST'], format: 'json')]
final class AddExpenseController extends AbstractController
{
    public function __invoke(string $id, #[MapRequestPayload] ExpensePayload $payload, AddExpenseHandler $addExpense): JsonResponse
    {
        $expenseId = $addExpense(new AddExpense($id, $payload->label, $payload->amount, $payload->sharedOverEvents, $payload->until()));

        return $this->json(['id' => (string) $expenseId], Response::HTTP_CREATED);
    }
}
