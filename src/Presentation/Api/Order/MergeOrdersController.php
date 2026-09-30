<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Application\Order\MergeOrders\MergeOrdersHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/orders/{id}/merge', name: 'api_orders_merge', requirements: ['id' => Requirement::ULID], methods: ['POST'], format: 'json')]
final class MergeOrdersController extends AbstractController
{
    public function __invoke(string $id, #[MapRequestPayload] MergeOrdersPayload $payload, MergeOrdersHandler $mergeOrders): Response
    {
        $mergeOrders($id, $payload->orderId);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
