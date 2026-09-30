<?php

declare(strict_types=1);

namespace App\Presentation\Api\Event;

use App\Application\Event\ListEvents\ListEventsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/events', name: 'api_events_list', methods: ['GET'], format: 'json')]
final class ListEventsController extends AbstractController
{
    public function __invoke(ListEventsHandler $listEvents): JsonResponse
    {
        return $this->json($listEvents());
    }
}
