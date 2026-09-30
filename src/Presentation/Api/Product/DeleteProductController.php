<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\DeleteProduct\DeleteProductHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/products/{id}', name: 'api_products_delete', requirements: ['id' => Requirement::ULID], methods: ['DELETE'], format: 'json')]
final class DeleteProductController extends AbstractController
{
    public function __invoke(string $id, DeleteProductHandler $deleteProduct): Response
    {
        $deleteProduct($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
