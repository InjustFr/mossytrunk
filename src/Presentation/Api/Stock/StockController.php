<?php

declare(strict_types=1);

namespace App\Presentation\Api\Stock;

use App\Application\Stock\DismissDiscrepancy\DismissDiscrepancyHandler;
use App\Application\Stock\GetProductStock\GetProductStockHandler;
use App\Application\Stock\GetStockSheet\GetStockSheetHandler;
use App\Application\Stock\ListStockChecks\ListStockChecksHandler;
use App\Application\Stock\Restock\RestockHandler;
use App\Application\Stock\TakeStockCheck\TakeStockCheckHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api', format: 'json')]
final class StockController extends AbstractController
{
    #[Route('/stock/restock', name: 'api_stock_restock', methods: ['POST'])]
    public function restock(#[MapRequestPayload] RestockPayload $payload, RestockHandler $restock): Response
    {
        $restock($payload->toCommand());

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/products/{id}/stock', name: 'api_products_stock', requirements: ['id' => Requirement::ULID], methods: ['GET'])]
    public function productStock(string $id, GetProductStockHandler $getProductStock): JsonResponse
    {
        return $this->json($getProductStock($id));
    }

    #[Route('/events/{id}/stock-sheet', name: 'api_events_stock_sheet', requirements: ['id' => Requirement::ULID], methods: ['GET'])]
    public function stockSheet(string $id, GetStockSheetHandler $getStockSheet): JsonResponse
    {
        return $this->json($getStockSheet($id));
    }

    #[Route('/events/{id}/stock-checks', name: 'api_events_stock_checks', requirements: ['id' => Requirement::ULID], methods: ['GET'])]
    public function stockChecks(string $id, ListStockChecksHandler $listStockChecks): JsonResponse
    {
        return $this->json($listStockChecks($id));
    }

    #[Route('/events/{id}/stock-checks', name: 'api_events_stock_checks_take', requirements: ['id' => Requirement::ULID], methods: ['POST'])]
    public function takeStockCheck(string $id, #[MapRequestPayload] StockCheckPayload $payload, TakeStockCheckHandler $takeStockCheck): JsonResponse
    {
        return $this->json(['id' => (string) $takeStockCheck($payload->toCommand($id))], Response::HTTP_CREATED);
    }

    #[Route('/stock-checks/{id}/lines/{lineId}/dismissal', name: 'api_stock_checks_dismiss', requirements: ['id' => Requirement::ULID, 'lineId' => Requirement::ULID], methods: ['POST'])]
    public function dismiss(string $id, string $lineId, DismissDiscrepancyHandler $dismiss): Response
    {
        $dismiss($id, $lineId);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
