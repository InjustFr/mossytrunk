<?php

declare(strict_types=1);

namespace App\Presentation\Api\Notebook;

use App\Application\Notebook\ScanNotebook\ScanNotebookHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/events/{id}/notebook', name: 'api_events_notebook_scan', requirements: ['id' => Requirement::ULID], methods: ['POST'], format: 'json')]
final class ScanNotebookController extends AbstractController
{
    public function __invoke(string $id, #[MapRequestPayload] NotebookPayload $payload, ScanNotebookHandler $scanNotebook): JsonResponse
    {
        return $this->json(['id' => (string) $scanNotebook($payload->toCommand($id))], Response::HTTP_CREATED);
    }
}
