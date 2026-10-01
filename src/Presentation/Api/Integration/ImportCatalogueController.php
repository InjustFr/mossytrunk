<?php

declare(strict_types=1);

namespace App\Presentation\Api\Integration;

use App\Application\Integration\Exception\UnreadableCatalogue;
use App\Application\Integration\ImportCatalogue\ImportCatalogueHandler;
use App\Presentation\RouteRequirement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/services/{service}/catalogue', name: 'api_services_catalogue_import', requirements: ['service' => RouteRequirement::SERVICE], methods: ['POST'], format: 'json')]
final class ImportCatalogueController extends AbstractController
{
    private const int MAX_BYTES = 5 * 1024 * 1024;

    public function __invoke(string $service, Request $request, ImportCatalogueHandler $import): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile || !$file->isValid() || $file->getSize() > self::MAX_BYTES) {
            throw new UnreadableCatalogue($service);
        }

        return $this->json($import($service, $file->getContent()));
    }
}
