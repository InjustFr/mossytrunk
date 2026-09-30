<?php

declare(strict_types=1);

namespace App\Presentation\Api\Workspace;

use App\Application\Workspace\GetSettings\GetWorkspaceSettingsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/workspace/settings', name: 'api_workspace_settings', methods: ['GET'], format: 'json')]
final class GetWorkspaceSettingsController extends AbstractController
{
    public function __invoke(GetWorkspaceSettingsHandler $settings): JsonResponse
    {
        return $this->json($settings());
    }
}
