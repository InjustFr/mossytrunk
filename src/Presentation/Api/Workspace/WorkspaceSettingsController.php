<?php

declare(strict_types=1);

namespace App\Presentation\Api\Workspace;

use App\Application\Workspace\GetSettings\GetWorkspaceSettingsHandler;
use App\Application\Workspace\RemoveSumUpApiKey\RemoveSumUpApiKeyHandler;
use App\Application\Workspace\UpdateSumUpSettings\UpdateSumUpSettingsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/workspace/settings', format: 'json')]
final class WorkspaceSettingsController extends AbstractController
{
    #[Route('', name: 'api_workspace_settings', methods: ['GET'])]
    public function show(GetWorkspaceSettingsHandler $settings): JsonResponse
    {
        return $this->json($settings());
    }

    #[Route('/sumup', name: 'api_workspace_settings_sumup', methods: ['PUT'])]
    public function updateSumUp(#[MapRequestPayload] SumUpSettingsPayload $payload, UpdateSumUpSettingsHandler $update): Response
    {
        $update($payload->toCommand());

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/sumup/api-key', name: 'api_workspace_settings_sumup_api_key', methods: ['DELETE'])]
    public function removeSumUpApiKey(RemoveSumUpApiKeyHandler $remove): Response
    {
        $remove();

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
