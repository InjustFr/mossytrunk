<?php

declare(strict_types=1);

namespace App\Presentation\Api\Integration;

use App\Application\Integration\PublishReferences\PublishReferencesHandler;
use App\Presentation\RouteRequirement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/services/{service}/references', name: 'api_services_references', requirements: ['service' => RouteRequirement::SERVICE], methods: ['POST'], format: 'json')]
final class PublishReferencesController extends AbstractController
{
    public function __invoke(string $service, PublishReferencesHandler $publish): JsonResponse
    {
        return $this->json($publish($service));
    }
}
