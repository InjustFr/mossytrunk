<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\WithdrawDeclination\WithdrawDeclinationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/designs/{id}/declinations/{declinationId}', name: 'api_designs_declination_withdraw', requirements: ['id' => Requirement::ULID, 'declinationId' => Requirement::ULID], methods: ['DELETE'], format: 'json')]
final class WithdrawDeclinationController extends AbstractController
{
    public function __invoke(string $id, string $declinationId, WithdrawDeclinationHandler $withdraw): Response
    {
        $withdraw($id, $declinationId);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
