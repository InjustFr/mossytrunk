<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\CreateProductType\CreateProductTypeHandler;
use App\Application\Product\ListProductTypes\ProductTypeView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/product-types', name: 'api_product_types_create', methods: ['POST'], format: 'json')]
final class CreateProductTypeController extends AbstractController
{
    public function __invoke(#[MapRequestPayload] ProductTypePayload $payload, CreateProductTypeHandler $createType): JsonResponse
    {
        return $this->json(ProductTypeView::fromType($createType($payload->name, $payload->color)), Response::HTTP_CREATED);
    }
}
