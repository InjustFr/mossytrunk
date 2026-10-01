<?php

declare(strict_types=1);

namespace App\Presentation\Api\Sales;

use App\Application\Sales\ListChannels\ListChannelsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/sales-channels', name: 'api_sales_channels', methods: ['GET'], format: 'json')]
final class ListChannelsController extends AbstractController
{
    public function __invoke(ListChannelsHandler $listChannels): JsonResponse
    {
        return $this->json($listChannels());
    }
}
