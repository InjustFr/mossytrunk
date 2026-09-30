<?php

declare(strict_types=1);

namespace App\Presentation\Api\Stock;

use App\Application\Stock\GetProductStock\GetProductStockHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/products/{id}/stock', name: 'api_products_stock', requirements: ['id' => Requirement::ULID], methods: ['GET'], format: 'json')]
final class GetProductStockController extends AbstractController
{
    public function __invoke(string $id, GetProductStockHandler $getProductStock): JsonResponse
    {
        return $this->json($getProductStock($id));
    }
}
