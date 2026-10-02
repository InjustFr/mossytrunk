<?php

declare(strict_types=1);

namespace App\Presentation\Api\Purchasing;

use App\Application\Purchasing\MergeSupplierOrders\MergeSupplierOrdersHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/supplier-orders/{id}/merge', name: 'api_supplier_orders_merge', requirements: ['id' => Requirement::ULID], methods: ['POST'], format: 'json')]
final class MergeSupplierOrdersController extends AbstractController
{
    public function __invoke(string $id, #[MapRequestPayload] MergeSupplierOrdersPayload $payload, MergeSupplierOrdersHandler $mergeOrders): Response
    {
        $mergeOrders($id, $payload->orderId);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
