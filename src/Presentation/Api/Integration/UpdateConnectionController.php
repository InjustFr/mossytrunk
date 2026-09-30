<?php

declare(strict_types=1);

namespace App\Presentation\Api\Integration;

use App\Application\Integration\ConfigureConnection\UpdateConnectionHandler;
use App\Presentation\RouteRequirement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/services/{service}', name: 'api_services_update', requirements: ['service' => RouteRequirement::SERVICE], methods: ['PUT'], format: 'json')]
final class UpdateConnectionController extends AbstractController
{
    public function __invoke(string $service, Request $request, ConnectionPayload $payload, UpdateConnectionHandler $update): Response
    {
        $update($payload->settings($request, $service, false));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
