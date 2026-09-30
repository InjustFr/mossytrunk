<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\BatchUpdateProducts\BatchUpdateProductsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products/batch', name: 'api_products_batch', methods: ['POST'], format: 'json')]
final class BatchUpdateProductsController extends AbstractController
{
    public function __invoke(#[MapRequestPayload] BatchProductsPayload $payload, BatchUpdateProductsHandler $batchUpdate): JsonResponse
    {
        return $this->json(['updated' => $batchUpdate($payload->toCommand())]);
    }
}
