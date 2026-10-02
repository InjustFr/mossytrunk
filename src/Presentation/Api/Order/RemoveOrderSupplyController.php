<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Application\Order\RemoveOrderSupply\RemoveOrderSupplyHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/orders/{id}/supplies/{supplyLineId}', name: 'api_orders_supplies_remove', requirements: ['id' => Requirement::ULID, 'supplyLineId' => Requirement::ULID], methods: ['DELETE'], format: 'json')]
final class RemoveOrderSupplyController extends AbstractController
{
    public function __invoke(string $id, string $supplyLineId, RemoveOrderSupplyHandler $removeSupply): Response
    {
        $removeSupply($id, $supplyLineId);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
