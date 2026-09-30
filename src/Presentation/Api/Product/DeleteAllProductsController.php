<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\DeleteAllProducts\DeleteAllProductsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products', name: 'api_products_delete_all', methods: ['DELETE'], format: 'json')]
final class DeleteAllProductsController extends AbstractController
{
    public function __invoke(DeleteAllProductsHandler $deleteAllProducts): JsonResponse
    {
        return $this->json(['deleted' => $deleteAllProducts()]);
    }
}
