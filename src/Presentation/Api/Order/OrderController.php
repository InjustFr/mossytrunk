<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Application\Order\DeleteOrder\DeleteOrderHandler;
use App\Application\Order\GetOrder\GetOrderHandler;
use App\Application\Order\ListOrders\ListOrdersHandler;
use App\Application\Order\PlaceOrder\PlaceOrder;
use App\Application\Order\PlaceOrder\PlaceOrderHandler;
use App\Application\Order\PreviewOrder\PreviewOrderHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/orders', format: 'json')]
final class OrderController extends AbstractController
{
    #[Route('', name: 'api_orders_list', methods: ['GET'])]
    public function list(ListOrdersHandler $listOrders, #[MapQueryParameter(filter: \FILTER_VALIDATE_REGEXP, options: ['regexp' => '/^[0-7][0-9A-HJKMNP-TV-Z]{25}$/'])] ?string $eventId = null): JsonResponse
    {
        return $this->json($listOrders($eventId));
    }

    #[Route('', name: 'api_orders_place', methods: ['POST'])]
    public function place(#[MapRequestPayload] OrderPayload $payload, PlaceOrderHandler $placeOrder): JsonResponse
    {
        $order = $placeOrder(new PlaceOrder($payload->placedAt(), $payload->requestedLines()));

        return $this->json(['id' => (string) $order->id(), 'reference' => $order->reference()], Response::HTTP_CREATED);
    }

    #[Route('/preview', name: 'api_orders_preview', methods: ['POST'])]
    public function preview(#[MapRequestPayload] OrderPayload $payload, PreviewOrderHandler $previewOrder): JsonResponse
    {
        return $this->json($previewOrder($payload->placedAt(), $payload->requestedLines()));
    }

    #[Route('/{id}', name: 'api_orders_show', requirements: ['id' => Requirement::ULID], methods: ['GET'])]
    public function show(string $id, GetOrderHandler $getOrder): JsonResponse
    {
        return $this->json($getOrder($id));
    }

    #[Route('/{id}', name: 'api_orders_delete', requirements: ['id' => Requirement::ULID], methods: ['DELETE'])]
    public function delete(string $id, DeleteOrderHandler $deleteOrder): Response
    {
        $deleteOrder($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
