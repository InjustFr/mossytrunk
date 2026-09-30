<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\ListProductTypes\ListProductTypesHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/product-types', name: 'api_product_types_list', methods: ['GET'], format: 'json')]
final class ListProductTypesController extends AbstractController
{
    public function __invoke(ListProductTypesHandler $listTypes): JsonResponse
    {
        return $this->json($listTypes());
    }
}
