<?php

declare(strict_types=1);

namespace App\Presentation\Api\Reference;

use App\Application\Reference\ListReferenceFormats\ListReferenceFormatsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/references/formats', name: 'api_reference_formats', methods: ['GET'], format: 'json')]
final class ListReferenceFormatsController extends AbstractController
{
    public function __invoke(ListReferenceFormatsHandler $formats): JsonResponse
    {
        return $this->json($formats());
    }
}
