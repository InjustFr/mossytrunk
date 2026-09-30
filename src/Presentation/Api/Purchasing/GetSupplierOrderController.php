<?php

declare(strict_types=1);

namespace App\Presentation\Api\Purchasing;

use App\Application\Purchasing\GetSupplierOrder\GetSupplierOrderHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/supplier-orders/{id}', name: 'api_supplier_orders_show', requirements: ['id' => Requirement::ULID], methods: ['GET'], format: 'json')]
final class GetSupplierOrderController extends AbstractController
{
    public function __invoke(string $id, GetSupplierOrderHandler $getOrder): JsonResponse
    {
        return $this->json($getOrder($id));
    }
}
