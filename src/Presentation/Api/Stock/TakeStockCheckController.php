<?php

declare(strict_types=1);

namespace App\Presentation\Api\Stock;

use App\Application\Stock\TakeStockCheck\TakeStockCheckHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/events/{id}/stock-checks', name: 'api_events_stock_checks_take', requirements: ['id' => Requirement::ULID], methods: ['POST'], format: 'json')]
final class TakeStockCheckController extends AbstractController
{
    public function __invoke(string $id, #[MapRequestPayload] StockCheckPayload $payload, TakeStockCheckHandler $takeStockCheck): JsonResponse
    {
        return $this->json(['id' => (string) $takeStockCheck($payload->toCommand($id))], Response::HTTP_CREATED);
    }
}
