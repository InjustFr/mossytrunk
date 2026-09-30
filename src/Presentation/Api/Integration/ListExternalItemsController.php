<?php

declare(strict_types=1);

namespace App\Presentation\Api\Integration;

use App\Application\Integration\ListExternalItems\ListExternalItemsHandler;
use App\Presentation\RouteRequirement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/services/{service}/items', name: 'api_services_items', requirements: ['service' => RouteRequirement::SERVICE], methods: ['GET'], format: 'json')]
final class ListExternalItemsController extends AbstractController
{
    public function __invoke(string $service, ListExternalItemsHandler $items): JsonResponse
    {
        return $this->json($items($service));
    }
}
