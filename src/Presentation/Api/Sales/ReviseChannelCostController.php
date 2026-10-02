<?php

declare(strict_types=1);

namespace App\Presentation\Api\Sales;

use App\Application\Sales\ReviseChannelCost\ReviseChannelCostHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/sales-channels/{id}/costs/{costId}', name: 'api_sales_channels_costs_revise', requirements: ['id' => Requirement::ULID, 'costId' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class ReviseChannelCostController extends AbstractController
{
    public function __invoke(string $id, string $costId, #[MapRequestPayload] ChannelCostPayload $payload, ReviseChannelCostHandler $reviseCost): Response
    {
        $reviseCost($id, $costId, $payload->label, $payload->kind(), $payload->amount);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
