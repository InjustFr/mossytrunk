<?php

declare(strict_types=1);

namespace App\Presentation\Api\Sales;

use App\Application\Sales\RemoveChannelCost\RemoveChannelCostHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/sales-channels/{id}/costs/{costId}', name: 'api_sales_channels_costs_remove', requirements: ['id' => Requirement::ULID, 'costId' => Requirement::ULID], methods: ['DELETE'], format: 'json')]
final class RemoveChannelCostController extends AbstractController
{
    public function __invoke(string $id, string $costId, RemoveChannelCostHandler $removeCost): Response
    {
        $removeCost($id, $costId);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
