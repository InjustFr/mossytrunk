<?php

declare(strict_types=1);

namespace App\Presentation\Api\Integration;

use App\Application\Integration\ReadCatalogue\ReadCatalogueHandler;
use App\Presentation\RouteRequirement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/services/{service}/catalogue/read', name: 'api_services_catalogue_read', requirements: ['service' => RouteRequirement::SERVICE], methods: ['POST'], format: 'json')]
final class ReadCatalogueController extends AbstractController
{
    public function __invoke(string $service, ReadCatalogueHandler $read): JsonResponse
    {
        return $this->json($read($service));
    }
}
