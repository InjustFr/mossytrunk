<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Application\Product\ListProductTypes\ListProductTypesHandler;
use App\Application\Product\ListProductTypes\ProductTypeView;
use App\Application\Product\RenameProductType\RenameProductTypeHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/product-types', format: 'json')]
final class ProductTypeController extends AbstractController
{
    #[Route('', name: 'api_product_types_list', methods: ['GET'])]
    public function list(ListProductTypesHandler $listTypes): JsonResponse
    {
        return $this->json($listTypes());
    }

    #[Route('', name: 'api_product_types_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] ProductTypePayload $payload, CreateProductTypeHandler $createType): JsonResponse
    {
        return $this->json(ProductTypeView::fromType($createType($payload->name)), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_product_types_rename', requirements: ['id' => Requirement::ULID], methods: ['PUT'])]
    public function rename(string $id, #[MapRequestPayload] ProductTypePayload $payload, RenameProductTypeHandler $renameType): Response
    {
        $renameType($id, $payload->name);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
