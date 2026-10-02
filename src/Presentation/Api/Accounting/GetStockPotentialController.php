<?php

declare(strict_types=1);

namespace App\Presentation\Api\Accounting;

use App\Application\Accounting\GetStockPotential\GetStockPotentialHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/accounting/stock-potential', name: 'api_accounting_stock_potential', methods: ['GET'], format: 'json')]
final class GetStockPotentialController extends AbstractController
{
    public function __invoke(GetStockPotentialHandler $getStockPotential): JsonResponse
    {
        return $this->json($getStockPotential()->toArray());
    }
}
