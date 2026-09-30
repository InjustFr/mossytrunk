<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\CreateProduct\CreateProduct;
use App\Application\Product\CreateProduct\CreateProductHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products', name: 'api_products_create', methods: ['POST'], format: 'json')]
final class CreateProductController extends AbstractController
{
    public function __invoke(#[MapRequestPayload] ProductPayload $payload, CreateProductHandler $createProduct): JsonResponse
    {
        $id = $createProduct(new CreateProduct(
            $payload->name,
            $payload->sellingPrice,
            $payload->variants,
            $payload->typeId,
            $payload->lowStockThreshold,
            $payload->reference,
        ));

        return $this->json(['id' => (string) $id], Response::HTTP_CREATED);
    }
}
