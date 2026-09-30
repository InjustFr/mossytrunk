<?php

declare(strict_types=1);

namespace App\Presentation\Api\Event;

use App\Application\Event\ReviseExpense\ReviseExpense;
use App\Application\Event\ReviseExpense\ReviseExpenseHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/events/{id}/expenses/{expenseId}', name: 'api_events_revise_expense', requirements: ['id' => Requirement::ULID, 'expenseId' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class ReviseExpenseController extends AbstractController
{
    public function __invoke(string $id, string $expenseId, #[MapRequestPayload] ExpensePayload $payload, ReviseExpenseHandler $reviseExpense): Response
    {
        $reviseExpense(new ReviseExpense($id, $expenseId, $payload->label, $payload->amount));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
