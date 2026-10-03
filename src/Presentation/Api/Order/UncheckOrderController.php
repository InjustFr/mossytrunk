<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Application\Order\UncheckOrder\UncheckOrderHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/orders/{id}/check', name: 'api_orders_uncheck', requirements: ['id' => Requirement::ULID], methods: ['DELETE'], format: 'json')]
final class UncheckOrderController extends AbstractController
{
    public function __invoke(string $id, UncheckOrderHandler $uncheckOrder): Response
    {
        $uncheckOrder($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
