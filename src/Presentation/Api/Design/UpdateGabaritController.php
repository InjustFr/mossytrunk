<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\GabaritView;
use App\Application\Design\SaveGabarit\SaveGabaritHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/gabarits/{id}', name: 'api_gabarits_update', requirements: ['id' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class UpdateGabaritController extends AbstractController
{
    public function __invoke(string $id, #[MapRequestPayload] GabaritPayload $payload, SaveGabaritHandler $saveGabarit): JsonResponse
    {
        return $this->json(GabaritView::of($saveGabarit($payload->toCommand($id))));
    }
}
