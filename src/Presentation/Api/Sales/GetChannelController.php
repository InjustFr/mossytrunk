<?php

declare(strict_types=1);

namespace App\Presentation\Api\Sales;

use App\Application\Sales\GetChannel\GetChannelHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/sales-channels/{id}', name: 'api_sales_channels_show', requirements: ['id' => Requirement::ULID], methods: ['GET'], format: 'json')]
final class GetChannelController extends AbstractController
{
    public function __invoke(string $id, GetChannelHandler $getChannel): JsonResponse
    {
        return $this->json($getChannel($id));
    }
}
