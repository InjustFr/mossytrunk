<?php

declare(strict_types=1);

namespace App\Presentation\Api\Integration;

use App\Application\Integration\ExportCatalogue\ExportCatalogueHandler;
use App\Presentation\RouteRequirement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/services/{service}/catalogue.csv', name: 'api_services_catalogue', requirements: ['service' => RouteRequirement::SERVICE], methods: ['GET'], format: 'csv')]
final class ExportCatalogueController extends AbstractController
{
    public function __invoke(string $service, ExportCatalogueHandler $export): Response
    {
        $file = $export($service);

        return new Response($file->content, headers: [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $file->filename),
            'X-Item-Count' => (string) $file->itemCount,
        ]);
    }
}
