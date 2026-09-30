<?php

declare(strict_types=1);

namespace App\Presentation\Api\Purchasing;

use App\Application\Purchasing\ReceiveSupplierOrder\ReceiveSupplierOrderHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/supplier-orders/{id}/reception', name: 'api_supplier_orders_receive', requirements: ['id' => Requirement::ULID], methods: ['POST'], format: 'json')]
final class ReceiveSupplierOrderController extends AbstractController
{
    public function __invoke(string $id, #[MapRequestPayload] ReceptionPayload $payload, ReceiveSupplierOrderHandler $receiveOrder): Response
    {
        $receiveOrder($id, $payload->receivedQuantities());

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
