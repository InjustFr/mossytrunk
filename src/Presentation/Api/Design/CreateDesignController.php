<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\CreateDesign\CreateDesignHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/designs', name: 'api_designs_create', methods: ['POST'], format: 'json')]
final class CreateDesignController extends AbstractController
{
    public function __invoke(#[MapRequestPayload] DesignPayload $payload, CreateDesignHandler $createDesign): JsonResponse
    {
        return $this->json(['id' => (string) $createDesign($payload->toCreate())], Response::HTTP_CREATED);
    }
}
