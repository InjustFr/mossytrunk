<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Application\Order\AddOrderSupplies\AddOrderSuppliesHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders/supplies', name: 'api_orders_supplies_add', methods: ['POST'], format: 'json')]
final class AddOrderSuppliesController extends AbstractController
{
    public function __invoke(#[MapRequestPayload] OrderSuppliesPayload $payload, AddOrderSuppliesHandler $addSupplies): JsonResponse
    {
        return $this->json(['updated' => $addSupplies($payload->toCommand())]);
    }
}
