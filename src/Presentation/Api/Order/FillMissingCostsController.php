<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Application\Order\FillMissingCosts\FillMissingCostsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders/missing-costs', name: 'api_orders_missing_costs', methods: ['POST'], format: 'json')]
final class FillMissingCostsController extends AbstractController
{
    public function __invoke(FillMissingCostsHandler $fillMissingCosts): JsonResponse
    {
        return $this->json(['filled' => $fillMissingCosts()]);
    }
}
