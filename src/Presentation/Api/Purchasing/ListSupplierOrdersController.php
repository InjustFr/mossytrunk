<?php

declare(strict_types=1);

namespace App\Presentation\Api\Purchasing;

use App\Application\Purchasing\ListSupplierOrders\ListSupplierOrdersHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/supplier-orders', name: 'api_supplier_orders_list', methods: ['GET'], format: 'json')]
final class ListSupplierOrdersController extends AbstractController
{
    public function __invoke(ListSupplierOrdersHandler $listOrders): JsonResponse
    {
        return $this->json($listOrders());
    }
}
