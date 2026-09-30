<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\TickAdaptation\TickAdaptationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/designs/{id}/declinations/{declinationId}/adaptations', name: 'api_designs_declination_tick', requirements: ['id' => Requirement::ULID, 'declinationId' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class TickAdaptationController extends AbstractController
{
    public function __invoke(string $id, string $declinationId, Request $request, TickAdaptationHandler $tick): Response
    {
        $payload = $request->getPayload();
        $tick($id, $declinationId, (string) $payload->get('adaptation'), (bool) $payload->get('done'));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
