<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Application\Order\ListOrders\ListOrdersHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders', name: 'api_orders_list', methods: ['GET'], format: 'json')]
final class ListOrdersController extends AbstractController
{
    public function __invoke(ListOrdersHandler $listOrders, #[MapQueryParameter(filter: \FILTER_VALIDATE_REGEXP, options: ['regexp' => '/^[0-7][0-9A-HJKMNP-TV-Z]{25}$/'])] ?string $eventId = null): JsonResponse
    {
        return $this->json($listOrders($eventId));
    }
}
