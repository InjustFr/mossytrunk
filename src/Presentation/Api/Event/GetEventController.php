<?php

declare(strict_types=1);

namespace App\Presentation\Api\Event;

use App\Application\Event\GetEvent\GetEventHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/events/{id}', name: 'api_events_show', requirements: ['id' => Requirement::ULID], methods: ['GET'], format: 'json')]
final class GetEventController extends AbstractController
{
    public function __invoke(string $id, GetEventHandler $getEvent): JsonResponse
    {
        return $this->json($getEvent($id));
    }
}
