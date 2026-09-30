<?php

declare(strict_types=1);

namespace App\Presentation\Api\Integration;

use App\Application\Integration\RemoveConnection\RemoveConnectionHandler;
use App\Presentation\RouteRequirement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/services/{service}', name: 'api_services_remove', requirements: ['service' => RouteRequirement::SERVICE], methods: ['DELETE'], format: 'json')]
final class RemoveConnectionController extends AbstractController
{
    public function __invoke(string $service, RemoveConnectionHandler $remove): Response
    {
        $remove($service);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
