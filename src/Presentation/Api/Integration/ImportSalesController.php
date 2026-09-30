<?php

declare(strict_types=1);

namespace App\Presentation\Api\Integration;

use App\Application\Integration\ImportSales\ImportSalesHandler;
use App\Presentation\RouteRequirement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/services/{service}/import', name: 'api_services_import', requirements: ['service' => RouteRequirement::SERVICE], methods: ['POST'], format: 'json')]
final class ImportSalesController extends AbstractController
{
    private const int IMPORT_TIME_LIMIT_SECONDS = 300;

    public function __invoke(string $service, ImportSalesHandler $import): JsonResponse
    {
        set_time_limit(self::IMPORT_TIME_LIMIT_SECONDS);

        return $this->json($import($service));
    }
}
