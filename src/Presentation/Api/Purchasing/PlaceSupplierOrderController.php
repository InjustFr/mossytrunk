<?php

declare(strict_types=1);

namespace App\Presentation\Api\Purchasing;

use App\Application\Purchasing\PlaceSupplierOrder\PlaceSupplierOrderHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/supplier-orders', name: 'api_supplier_orders_create', methods: ['POST'], format: 'json')]
final class PlaceSupplierOrderController extends AbstractController
{
    public function __invoke(#[MapRequestPayload] SupplierOrderPayload $payload, PlaceSupplierOrderHandler $placeOrder): JsonResponse
    {
        return $this->json(['id' => (string) $placeOrder($payload->toDraft())], Response::HTTP_CREATED);
    }
}
