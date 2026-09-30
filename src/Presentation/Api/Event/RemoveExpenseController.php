<?php

declare(strict_types=1);

namespace App\Presentation\Api\Event;

use App\Application\Event\RemoveExpense\RemoveExpense;
use App\Application\Event\RemoveExpense\RemoveExpenseHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/events/{id}/expenses/{expenseId}', name: 'api_events_remove_expense', requirements: ['id' => Requirement::ULID, 'expenseId' => Requirement::ULID], methods: ['DELETE'], format: 'json')]
final class RemoveExpenseController extends AbstractController
{
    public function __invoke(string $id, string $expenseId, RemoveExpenseHandler $removeExpense): Response
    {
        $removeExpense(new RemoveExpense($id, $expenseId));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
