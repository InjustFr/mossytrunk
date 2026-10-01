<?php

declare(strict_types=1);

namespace App\Presentation\Api\Sales;

use App\Application\Sales\SaveChannel\SaveChannelHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/sales-channels', name: 'api_sales_channels_create', methods: ['POST'], format: 'json')]
final class CreateChannelController extends AbstractController
{
    public function __invoke(#[MapRequestPayload] ChannelPayload $payload, SaveChannelHandler $saveChannel): JsonResponse
    {
        return $this->json(['id' => (string) $saveChannel(null, $payload->name, $payload->kind(), $payload->service)], Response::HTTP_CREATED);
    }
}
