<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\GetProduct\GetProductHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/products/{id}', name: 'api_products_show', requirements: ['id' => Requirement::ULID], methods: ['GET'], format: 'json')]
final class GetProductController extends AbstractController
{
    public function __invoke(string $id, GetProductHandler $getProduct): JsonResponse
    {
        return $this->json($getProduct($id));
    }
}
