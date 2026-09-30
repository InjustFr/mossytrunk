<?php

declare(strict_types=1);

namespace App\Presentation\Api\Event;

use App\Application\Event\GetEventReport\GetEventReportHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/events/{id}/report', name: 'api_events_report', requirements: ['id' => Requirement::ULID], methods: ['GET'], format: 'json')]
final class GetEventReportController extends AbstractController
{
    public function __invoke(string $id, GetEventReportHandler $getReport): JsonResponse
    {
        return $this->json($getReport($id));
    }
}
