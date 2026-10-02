<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\SetChannelPrice\SetChannelPriceHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/products/{id}/channel-prices/{channelId}', name: 'api_products_channel_price', requirements: ['id' => Requirement::ULID, 'channelId' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class SetChannelPriceController extends AbstractController
{
    public function __invoke(string $id, string $channelId, #[MapRequestPayload] ChannelPricePayload $payload, SetChannelPriceHandler $setChannelPrice): Response
    {
        $setChannelPrice($id, $channelId, $payload->price);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
