<?php

declare(strict_types=1);

namespace App\Presentation\Api\Stock;

use App\Application\Stock\ListStockChecks\ListStockChecksHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/events/{id}/stock-checks', name: 'api_events_stock_checks', requirements: ['id' => Requirement::ULID], methods: ['GET'], format: 'json')]
final class ListStockChecksController extends AbstractController
{
    public function __invoke(string $id, ListStockChecksHandler $listStockChecks): JsonResponse
    {
        return $this->json($listStockChecks($id));
    }
}
