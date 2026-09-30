<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\AdjustDeclination\AdjustDeclinationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/designs/{id}/declinations/{declinationId}', name: 'api_designs_declination_adjust', requirements: ['id' => Requirement::ULID, 'declinationId' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class AdjustDeclinationController extends AbstractController
{
    public function __invoke(string $id, string $declinationId, #[MapRequestPayload] DeclinationPayload $payload, AdjustDeclinationHandler $adjust): Response
    {
        $adjust($payload->toCommand($id, $declinationId));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
