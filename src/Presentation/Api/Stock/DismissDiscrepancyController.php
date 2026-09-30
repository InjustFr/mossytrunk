<?php

declare(strict_types=1);

namespace App\Presentation\Api\Stock;

use App\Application\Stock\DismissDiscrepancy\DismissDiscrepancyHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/stock-checks/{id}/lines/{lineId}/dismissal', name: 'api_stock_checks_dismiss', requirements: ['id' => Requirement::ULID, 'lineId' => Requirement::ULID], methods: ['POST'], format: 'json')]
final class DismissDiscrepancyController extends AbstractController
{
    public function __invoke(string $id, string $lineId, DismissDiscrepancyHandler $dismiss): Response
    {
        $dismiss($id, $lineId);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
