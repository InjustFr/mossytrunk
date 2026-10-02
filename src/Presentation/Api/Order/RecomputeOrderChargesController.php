<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Application\Order\RecomputeOrderCharges\RecomputeOrderChargesHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders/charges', name: 'api_orders_charges', methods: ['POST'], format: 'json')]
final class RecomputeOrderChargesController extends AbstractController
{
    public function __invoke(#[MapRequestPayload] OrderSelectionPayload $payload, RecomputeOrderChargesHandler $recompute): JsonResponse
    {
        return $this->json(['updated' => $recompute($payload->orderIds)]);
    }
}
