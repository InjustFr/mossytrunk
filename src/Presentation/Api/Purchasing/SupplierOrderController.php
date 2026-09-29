<?php

declare(strict_types=1);

namespace App\Presentation\Api\Purchasing;

use App\Application\Purchasing\DeleteSupplierOrder\DeleteSupplierOrderHandler;
use App\Application\Purchasing\GetSupplierOrder\GetSupplierOrderHandler;
use App\Application\Purchasing\ListSupplierOrders\ListSupplierOrdersHandler;
use App\Application\Purchasing\PlaceSupplierOrder\PlaceSupplierOrderHandler;
use App\Application\Purchasing\ReceiveSupplierOrder\ReceiveSupplierOrderHandler;
use App\Application\Purchasing\ReviseSupplierOrder\ReviseSupplierOrderHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/supplier-orders', format: 'json')]
final class SupplierOrderController extends AbstractController
{
    #[Route('', name: 'api_supplier_orders_list', methods: ['GET'])]
    public function list(ListSupplierOrdersHandler $listOrders): JsonResponse
    {
        return $this->json($listOrders());
    }

    #[Route('', name: 'api_supplier_orders_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] SupplierOrderPayload $payload, PlaceSupplierOrderHandler $placeOrder): JsonResponse
    {
        return $this->json(['id' => (string) $placeOrder($payload->toDraft())], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_supplier_orders_show', requirements: ['id' => Requirement::ULID], methods: ['GET'])]
    public function show(string $id, GetSupplierOrderHandler $getOrder): JsonResponse
    {
        return $this->json($getOrder($id));
    }

    #[Route('/{id}', name: 'api_supplier_orders_update', requirements: ['id' => Requirement::ULID], methods: ['PUT'])]
    public function update(string $id, #[MapRequestPayload] SupplierOrderPayload $payload, ReviseSupplierOrderHandler $reviseOrder): Response
    {
        $reviseOrder($id, $payload->toDraft());

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}', name: 'api_supplier_orders_delete', requirements: ['id' => Requirement::ULID], methods: ['DELETE'])]
    public function delete(string $id, DeleteSupplierOrderHandler $deleteOrder): Response
    {
        $deleteOrder($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/reception', name: 'api_supplier_orders_receive', requirements: ['id' => Requirement::ULID], methods: ['POST'])]
    public function receive(string $id, #[MapRequestPayload] ReceptionPayload $payload, ReceiveSupplierOrderHandler $receiveOrder): Response
    {
        $receiveOrder($id, $payload->receivedQuantities());

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
