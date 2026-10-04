<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\CreateGabarit\CreateGabaritHandler;
use App\Application\Design\GabaritView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/gabarits', name: 'api_gabarits_create', methods: ['POST'], format: 'json')]
final class CreateGabaritController extends AbstractController
{
    public function __invoke(#[MapRequestPayload] GabaritPayload $payload, CreateGabaritHandler $createGabarit): JsonResponse
    {
        return $this->json(GabaritView::of($createGabarit($payload->toCreate())), Response::HTTP_CREATED);
    }
}
