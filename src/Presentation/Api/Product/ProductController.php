<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Design\DesignProduct\DesignProductHandler;
use App\Application\Product\BatchUpdateProducts\BatchUpdateProductsHandler;
use App\Application\Product\CreateProduct\CreateProduct;
use App\Application\Product\CreateProduct\CreateProductHandler;
use App\Application\Product\DeleteAllProducts\DeleteAllProductsHandler;
use App\Application\Product\DeleteProduct\DeleteProductHandler;
use App\Application\Product\GetProduct\GetProductHandler;
use App\Application\Product\ListProducts\ListProductsHandler;
use App\Application\Product\MoveVariant\MoveVariantHandler;
use App\Application\Product\ReviseSellingPrices\ReviseSellingPricesHandler;
use App\Application\Product\UpdateProduct\UpdateProduct;
use App\Application\Product\UpdateProduct\UpdateProductHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
            $payload->name,
            $payload->sellingPrice,
            $payload->variants,
            $payload->typeId,
            $payload->lowStockThreshold,
        ));

        return $this->json(['id' => (string) $id], Response::HTTP_CREATED);
    }

    #[Route('/batch', name: 'api_products_batch', methods: ['POST'])]
    public function batch(#[MapRequestPayload] BatchProductsPayload $payload, BatchUpdateProductsHandler $batchUpdate): JsonResponse
    {
        return $this->json(['updated' => $batchUpdate($payload->toCommand())]);
    }

    #[Route('/{id}', name: 'api_products_show', requirements: ['id' => Requirement::ULID], methods: ['GET'])]
    public function show(string $id, GetProductHandler $getProduct): JsonResponse
    {
        return $this->json($getProduct($id));
    }

    #[Route('/{id}/prices', name: 'api_products_prices_record', requirements: ['id' => Requirement::ULID], methods: ['POST'])]
    public function recordPrice(string $id, #[MapRequestPayload] SellingPricePayload $payload, ReviseSellingPricesHandler $prices): Response
    {
        $prices->record($id, $payload->price, $payload->since);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/prices/{changeId}', name: 'api_products_prices_amend', requirements: ['id' => Requirement::ULID, 'changeId' => Requirement::ULID], methods: ['PUT'])]
    public function amendPrice(string $id, string $changeId, #[MapRequestPayload] SellingPricePayload $payload, ReviseSellingPricesHandler $prices): Response
    {
        $prices->amend($id, $changeId, $payload->price, $payload->since);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/prices/{changeId}', name: 'api_products_prices_forget', requirements: ['id' => Requirement::ULID, 'changeId' => Requirement::ULID], methods: ['DELETE'])]
    public function forgetPrice(string $id, string $changeId, ReviseSellingPricesHandler $prices): Response
    {
        $prices->forget($id, $changeId);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/design', name: 'api_products_design', requirements: ['id' => Requirement::ULID], methods: ['POST'])]
    public function design(string $id, Request $request, DesignProductHandler $designProduct): JsonResponse
    {
        $payload = $request->getPayload();
        $gabaritId = $payload->getString('gabaritId');
        $designId = $payload->getString('designId');
        $collectionId = $payload->getString('collectionId');

        $design = '' === $designId
            ? $designProduct->create($id, $gabaritId, '' === $collectionId ? null : $collectionId)
            : $designProduct->attach($id, $designId, $gabaritId);

        return $this->json(['designId' => (string) $design], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_products_update', requirements: ['id' => Requirement::ULID], methods: ['PUT'])]
    public function update(string $id, #[MapRequestPayload] ProductPayload $payload, UpdateProductHandler $updateProduct): Response
    {
        $updateProduct(new UpdateProduct(
            $id,
            $payload->name,
            $payload->sellingPrice,
            $payload->variants,
            $payload->typeId,
            $payload->lowStockThreshold,
        ));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/move-variant', name: 'api_products_move_variant', requirements: ['id' => Requirement::ULID], methods: ['POST'])]
    public function moveVariant(string $id, #[MapRequestPayload] MoveVariantPayload $payload, MoveVariantHandler $moveVariant): JsonResponse
    {
        return $this->json(['targetProductId' => (string) $moveVariant($payload->toCommand($id))]);
    }

    #[Route('/{id}', name: 'api_products_delete', requirements: ['id' => Requirement::ULID], methods: ['DELETE'])]
    public function delete(string $id, DeleteProductHandler $deleteProduct): Response
    {
        $deleteProduct($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('', name: 'api_products_delete_all', methods: ['DELETE'])]
    public function deleteAll(DeleteAllProductsHandler $deleteAllProducts): JsonResponse
    {
        return $this->json(['deleted' => $deleteAllProducts()]);
    }
}
