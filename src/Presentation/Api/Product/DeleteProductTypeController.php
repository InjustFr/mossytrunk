<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\DeleteProductType\DeleteProductTypeHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/product-types/{id}', name: 'api_product_types_delete', requirements: ['id' => Requirement::ULID], methods: ['DELETE'], format: 'json')]
final class DeleteProductTypeController extends AbstractController
{
    public function __invoke(string $id, DeleteProductTypeHandler $deleteType): Response
    {
        $deleteType($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
