<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\CreateProduct\CreateProduct;
use App\Application\Product\CreateProduct\CreateProductHandler;
use App\Application\Product\ListProducts\ListProductsHandler;
use App\Application\Product\UpdateProduct\UpdateProduct;
use App\Application\Product\UpdateProduct\UpdateProductHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/products', format: 'json')]
final class ProductController extends AbstractController
{
    #[Route('', name: 'api_products_list', methods: ['GET'])]
    public function list(ListProductsHandler $listProducts): JsonResponse
    {
        return $this->json($listProducts());
    }

    #[Route('', name: 'api_products_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] ProductPayload $payload, CreateProductHandler $createProduct): JsonResponse
    {
        $id = $createProduct(new CreateProduct(
            $payload->reference,
            $payload->name,
            $payload->sellingPrice,
            $payload->buyingPrice,
            $payload->variants,
        ));

        return $this->json(['id' => (string) $id], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_products_update', requirements: ['id' => Requirement::ULID], methods: ['PUT'])]
    public function update(string $id, #[MapRequestPayload] ProductPayload $payload, UpdateProductHandler $updateProduct): Response
    {
        $updateProduct(new UpdateProduct(
            $id,
            $payload->reference,
            $payload->name,
            $payload->sellingPrice,
            $payload->buyingPrice,
            $payload->variants,
        ));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
