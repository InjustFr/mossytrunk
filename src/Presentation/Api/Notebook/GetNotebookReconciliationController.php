<?php

declare(strict_types=1);

namespace App\Presentation\Api\Notebook;

use App\Application\Notebook\GetNotebookReconciliation\GetNotebookReconciliationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/events/{id}/notebook', name: 'api_events_notebook', requirements: ['id' => Requirement::ULID], methods: ['GET'], format: 'json')]
final class GetNotebookReconciliationController extends AbstractController
{
    public function __invoke(string $id, GetNotebookReconciliationHandler $getReconciliation): JsonResponse
    {
        return $this->json($getReconciliation($id));
    }
}
