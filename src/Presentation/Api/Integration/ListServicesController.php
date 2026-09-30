<?php

declare(strict_types=1);

namespace App\Presentation\Api\Integration;

use App\Application\Integration\ListServices\ListServicesHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/services', name: 'api_services', methods: ['GET'], format: 'json')]
final class ListServicesController extends AbstractController
{
    public function __invoke(ListServicesHandler $services): JsonResponse
    {
        return $this->json($services());
    }
}
