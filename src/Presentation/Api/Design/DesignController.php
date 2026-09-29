<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\AdjustDeclination\AdjustDeclinationHandler;
use App\Application\Design\DeclineDesign\DeclineDesignHandler;
use App\Application\Design\DeleteDesign\DeleteDesignHandler;
use App\Application\Design\GetDesign\GetDesignHandler;
use App\Application\Design\ListDesigns\ListDesignsHandler;
use App\Application\Design\SaveCollection\SaveCollectionHandler;
use App\Application\Design\SaveDesign\SaveDesignHandler;
use App\Application\Design\TickAdaptation\TickAdaptationHandler;
use App\Application\Design\ValidateDesign\ValidateDesignHandler;
use App\Application\Design\WithdrawDeclination\WithdrawDeclinationHandler;
use App\Application\Design\WorkOn\WorkOnHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api', format: 'json')]
final class DesignController extends AbstractController
{
    #[Route('/designs', name: 'api_designs_board', methods: ['GET'])]
    public function board(ListDesignsHandler $listDesigns): JsonResponse
    {
        return $this->json($listDesigns());
    }

    #[Route('/designs', name: 'api_designs_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] DesignPayload $payload, SaveDesignHandler $saveDesign): JsonResponse
    {
        return $this->json(['id' => (string) $saveDesign($payload->toCommand())], Response::HTTP_CREATED);
    }

    #[Route('/designs/{id}', name: 'api_designs_show', requirements: ['id' => Requirement::ULID], methods: ['GET'])]
    public function show(string $id, GetDesignHandler $getDesign): JsonResponse
    {
        return $this->json($getDesign($id));
    }

    #[Route('/designs/{id}', name: 'api_designs_update', requirements: ['id' => Requirement::ULID], methods: ['PUT'])]
    public function update(string $id, #[MapRequestPayload] DesignPayload $payload, SaveDesignHandler $saveDesign): Response
    {
        $saveDesign($payload->toCommand($id));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/designs/{id}', name: 'api_designs_delete', requirements: ['id' => Requirement::ULID], methods: ['DELETE'])]
    public function delete(string $id, DeleteDesignHandler $deleteDesign): Response
    {
        $deleteDesign($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/designs/{id}/current', name: 'api_designs_current', requirements: ['id' => Requirement::ULID], methods: ['PUT'])]
    public function workOnDesign(string $id, Request $request, WorkOnHandler $workOn): Response
    {
        $workOn->design($id, (bool) $request->getPayload()->get('current'));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/designs/{id}/declinations', name: 'api_designs_decline', requirements: ['id' => Requirement::ULID], methods: ['POST'])]
    public function decline(string $id, Request $request, DeclineDesignHandler $decline): JsonResponse
    {
        return $this->json(['id' => (string) $decline($id, (string) $request->getPayload()->get('gabaritId'))], Response::HTTP_CREATED);
    }

    #[Route('/designs/{id}/declinations/{declinationId}', name: 'api_designs_declination_adjust', requirements: ['id' => Requirement::ULID, 'declinationId' => Requirement::ULID], methods: ['PUT'])]
    public function adjust(string $id, string $declinationId, #[MapRequestPayload] DeclinationPayload $payload, AdjustDeclinationHandler $adjust): Response
    {
        $adjust($payload->toCommand($id, $declinationId));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/designs/{id}/declinations/{declinationId}', name: 'api_designs_declination_withdraw', requirements: ['id' => Requirement::ULID, 'declinationId' => Requirement::ULID], methods: ['DELETE'])]
    public function withdraw(string $id, string $declinationId, WithdrawDeclinationHandler $withdraw): Response
    {
        $withdraw($id, $declinationId);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/designs/{id}/declinations/{declinationId}/adaptations', name: 'api_designs_declination_tick', requirements: ['id' => Requirement::ULID, 'declinationId' => Requirement::ULID], methods: ['PUT'])]
    public function tick(string $id, string $declinationId, Request $request, TickAdaptationHandler $tick): Response
    {
        $payload = $request->getPayload();
        $tick($id, $declinationId, (string) $payload->get('adaptation'), (bool) $payload->get('done'));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/designs/{id}/validation', name: 'api_designs_validate', requirements: ['id' => Requirement::ULID], methods: ['POST'])]
    public function validate(string $id, ValidateDesignHandler $validate): JsonResponse
    {
        return $this->json(['productsCreated' => $validate($id)]);
    }

    #[Route('/design-collections', name: 'api_design_collections_create', methods: ['POST'])]
    public function createCollection(#[MapRequestPayload] CollectionPayload $payload, SaveCollectionHandler $saveCollection): JsonResponse
    {
        return $this->json(['id' => (string) $saveCollection(null, $payload->name, $payload->description)], Response::HTTP_CREATED);
    }

    #[Route('/design-collections/{id}', name: 'api_design_collections_update', requirements: ['id' => Requirement::ULID], methods: ['PUT'])]
    public function updateCollection(string $id, #[MapRequestPayload] CollectionPayload $payload, SaveCollectionHandler $saveCollection): Response
    {
        $saveCollection($id, $payload->name, $payload->description);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/design-collections/{id}/current', name: 'api_design_collections_current', requirements: ['id' => Requirement::ULID], methods: ['PUT'])]
    public function workOnCollection(string $id, Request $request, WorkOnHandler $workOn): Response
    {
        $workOn->collection($id, (bool) $request->getPayload()->get('current'));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/design-collections/{id}/validation', name: 'api_design_collections_validate', requirements: ['id' => Requirement::ULID], methods: ['POST'])]
    public function validateCollection(string $id, ValidateDesignHandler $validate): JsonResponse
    {
        return $this->json(['productsCreated' => $validate->collection($id)]);
    }
}
