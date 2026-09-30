<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\DeleteProducts\DeleteProductsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products/deletion', name: 'api_products_delete_selection', methods: ['POST'], format: 'json')]
final class DeleteProductsController extends AbstractController
{
    public function __invoke(#[MapRequestPayload] DeleteProductsPayload $payload, DeleteProductsHandler $deleteProducts): JsonResponse
    {
        return $this->json(['deleted' => $deleteProducts($payload->toCommand())]);
    }
}
