<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Application\Order\CheckOrder\CheckOrderHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/orders/{id}/check', name: 'api_orders_check', requirements: ['id' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class CheckOrderController extends AbstractController
{
    public function __invoke(string $id, CheckOrderHandler $checkOrder): Response
    {
        $checkOrder($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
