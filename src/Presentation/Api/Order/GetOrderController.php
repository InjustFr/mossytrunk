<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Application\Order\GetOrder\GetOrderHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/orders/{id}', name: 'api_orders_show', requirements: ['id' => Requirement::ULID], methods: ['GET'], format: 'json')]
final class GetOrderController extends AbstractController
{
    public function __invoke(string $id, GetOrderHandler $getOrder): JsonResponse
    {
        return $this->json($getOrder($id));
    }
}
