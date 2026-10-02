<?php

declare(strict_types=1);

namespace App\Presentation\Api\Sales;

use App\Application\Sales\OfferSupplies\OfferSuppliesHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/sales-channels/{id}/supplies', name: 'api_sales_channels_supplies', requirements: ['id' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class OfferSuppliesController extends AbstractController
{
    public function __invoke(string $id, #[MapRequestPayload] ChannelSuppliesPayload $payload, OfferSuppliesHandler $offerSupplies): Response
    {
        $offerSupplies($id, $payload->supplyIds);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
