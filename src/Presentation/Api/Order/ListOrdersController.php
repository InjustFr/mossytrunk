<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Application\Order\ListOrders\ListOrdersHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/orders', name: 'api_orders_list', methods: ['GET'], format: 'json')]
final class ListOrdersController extends AbstractController
{
    public function __invoke(ListOrdersHandler $listOrders, #[MapQueryParameter(filter: \FILTER_VALIDATE_REGEXP, options: ['regexp' => '/^'.Requirement::ULID.'$/'])] ?string $eventId = null): JsonResponse
    {
        return new JsonResponse($listOrders($eventId));
    }
}
