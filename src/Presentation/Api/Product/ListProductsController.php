<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\ListProducts\ListProductsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products', name: 'api_products_list', methods: ['GET'], format: 'json')]
final class ListProductsController extends AbstractController
{
    public function __invoke(ListProductsHandler $listProducts): JsonResponse
    {
        return new JsonResponse($listProducts());
    }
}
