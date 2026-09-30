<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\RestoreProduct\RestoreProductHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/products/{id}/archive', name: 'api_products_restore', requirements: ['id' => Requirement::ULID], methods: ['DELETE'], format: 'json')]
final class RestoreProductController extends AbstractController
{
    public function __invoke(string $id, RestoreProductHandler $restoreProduct): Response
    {
        $restoreProduct($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
