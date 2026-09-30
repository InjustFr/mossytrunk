<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Application\Order\DeleteOrders\DeleteOrdersHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders/deletion', name: 'api_orders_delete_selection', methods: ['POST'], format: 'json')]
final class DeleteOrdersController extends AbstractController
{
    public function __invoke(#[MapRequestPayload] DeleteOrdersPayload $payload, DeleteOrdersHandler $deleteOrders): JsonResponse
    {
        return $this->json(['deleted' => $deleteOrders($payload->toCommand())]);
    }
}
