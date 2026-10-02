<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Application\Order\StampOrder\StampOrderHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/orders/{id}/postage', name: 'api_orders_postage', requirements: ['id' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class StampOrderController extends AbstractController
{
    public function __invoke(string $id, #[MapRequestPayload] PostagePayload $payload, StampOrderHandler $stamp): Response
    {
        $stamp($id, $payload->postage);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
