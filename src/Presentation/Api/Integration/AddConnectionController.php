<?php

declare(strict_types=1);

namespace App\Presentation\Api\Integration;

use App\Application\Integration\ConfigureConnection\AddConnectionHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/services', name: 'api_services_add', methods: ['POST'], format: 'json')]
final class AddConnectionController extends AbstractController
{
    public function __invoke(Request $request, ConnectionPayload $payload, AddConnectionHandler $add): Response
    {
        $add($payload->settings($request, $request->getPayload()->getString('service'), true));

        return new Response(status: Response::HTTP_CREATED);
    }
}
