<?php

declare(strict_types=1);

namespace App\Presentation\Api\Workspace;

use App\Application\Workspace\GetSettings\GetWorkspaceSettingsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/workspace/settings', format: 'json')]
final class WorkspaceSettingsController extends AbstractController
{
    #[Route('', name: 'api_workspace_settings', methods: ['GET'])]
    public function show(GetWorkspaceSettingsHandler $settings): JsonResponse
    {
        return $this->json($settings());
    }
}
