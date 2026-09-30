<?php

declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Application\Order\IdentifyOrderLine\IdentifyOrderLine;
use App\Application\Order\IdentifyOrderLine\IdentifyOrderLineHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/orders/{id}/lines/{lineId}/product', name: 'api_orders_identify_line', requirements: ['id' => Requirement::ULID, 'lineId' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class IdentifyOrderLineController extends AbstractController
{
    public function __invoke(string $id, string $lineId, #[MapRequestPayload] IdentifyOrderLinePayload $payload, IdentifyOrderLineHandler $identifyLine): Response
    {
        $identifyLine(new IdentifyOrderLine($id, $lineId, $payload->productId, $payload->variant));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
