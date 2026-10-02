<?php

declare(strict_types=1);

namespace App\Presentation\Api\Sales;

use App\Application\Sales\AddChannelCost\AddChannelCostHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/sales-channels/{id}/costs', name: 'api_sales_channels_costs_add', requirements: ['id' => Requirement::ULID], methods: ['POST'], format: 'json')]
final class AddChannelCostController extends AbstractController
{
    public function __invoke(string $id, #[MapRequestPayload] ChannelCostPayload $payload, AddChannelCostHandler $addCost): JsonResponse
    {
        return $this->json(['id' => (string) $addCost($id, $payload->label, $payload->kind(), $payload->amount)], Response::HTTP_CREATED);
    }
}
