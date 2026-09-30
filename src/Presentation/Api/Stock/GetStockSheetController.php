<?php

declare(strict_types=1);

namespace App\Presentation\Api\Stock;

use App\Application\Stock\GetStockSheet\GetStockSheetHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/events/{id}/stock-sheet', name: 'api_events_stock_sheet', requirements: ['id' => Requirement::ULID], methods: ['GET'], format: 'json')]
final class GetStockSheetController extends AbstractController
{
    public function __invoke(string $id, GetStockSheetHandler $getStockSheet): JsonResponse
    {
        return $this->json($getStockSheet($id));
    }
}
