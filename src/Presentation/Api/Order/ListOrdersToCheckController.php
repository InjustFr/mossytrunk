<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Application\Order\ListOrdersToCheck\ListOrdersToCheckHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/events/{id}/orders-to-check', name: 'api_events_orders_to_check', requirements: ['id' => Requirement::ULID], methods: ['GET'], format: 'json')]
final class ListOrdersToCheckController extends AbstractController
{
    public function __invoke(string $id, ListOrdersToCheckHandler $listOrdersToCheck): JsonResponse
    {
        return $this->json($listOrdersToCheck($id));
    }
}
