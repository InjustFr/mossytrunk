<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Application\Order\DeleteAllOrders\DeleteAllOrdersHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders', name: 'api_orders_delete_all', methods: ['DELETE'], format: 'json')]
final class DeleteAllOrdersController extends AbstractController
{
    public function __invoke(DeleteAllOrdersHandler $deleteAllOrders): JsonResponse
    {
        return $this->json(['deleted' => $deleteAllOrders()]);
    }
}
