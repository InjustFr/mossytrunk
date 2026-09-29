<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\GabaritView;
use App\Application\Design\ListGabarits\ListGabaritsHandler;
use App\Application\Design\SaveGabarit\SaveGabaritHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/gabarits', format: 'json')]
final class GabaritController extends AbstractController
{
    #[Route('', name: 'api_gabarits_list', methods: ['GET'])]
    public function list(ListGabaritsHandler $listGabarits): JsonResponse
    {
        return $this->json($listGabarits());
    }

    #[Route('', name: 'api_gabarits_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] GabaritPayload $payload, SaveGabaritHandler $saveGabarit): JsonResponse
    {
        return $this->json(GabaritView::of($saveGabarit($payload->toCommand())), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_gabarits_update', requirements: ['id' => Requirement::ULID], methods: ['PUT'])]
    public function update(string $id, #[MapRequestPayload] GabaritPayload $payload, SaveGabaritHandler $saveGabarit): JsonResponse
    {
        return $this->json(GabaritView::of($saveGabarit($payload->toCommand($id))));
    }
}
