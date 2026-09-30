<?php

declare(strict_types=1);

namespace App\Presentation\Api\Integration;

use App\Application\Integration\DisconnectService\DisconnectServiceHandler;
use App\Presentation\RouteRequirement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/services/{service}/authorization', name: 'api_services_disconnect', requirements: ['service' => RouteRequirement::SERVICE], methods: ['DELETE'], format: 'json')]
final class DisconnectServiceController extends AbstractController
{
    public function __invoke(string $service, DisconnectServiceHandler $disconnect): Response
    {
        $disconnect($service);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
