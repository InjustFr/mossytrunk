<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Application\Order\RefundOrder\RefundOrderHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/orders/{id}/refund', name: 'api_orders_refund', requirements: ['id' => Requirement::ULID], methods: ['POST'], format: 'json')]
final class RefundOrderController extends AbstractController
{
    public function __invoke(string $id, RefundOrderHandler $refundOrder): Response
    {
        $refundOrder($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
