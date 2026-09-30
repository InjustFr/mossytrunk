<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Application\Order\DeleteOrder\DeleteOrderHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/orders/{id}', name: 'api_orders_delete', requirements: ['id' => Requirement::ULID], methods: ['DELETE'], format: 'json')]
final class DeleteOrderController extends AbstractController
{
    public function __invoke(string $id, DeleteOrderHandler $deleteOrder): Response
    {
        $deleteOrder($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
