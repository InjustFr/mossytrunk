<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\RestoreProductType\RestoreProductTypeHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/product-types/{id}/archive', name: 'api_product_types_restore', requirements: ['id' => Requirement::ULID], methods: ['DELETE'], format: 'json')]
final class RestoreProductTypeController extends AbstractController
{
    public function __invoke(string $id, RestoreProductTypeHandler $restoreType): Response
    {
        $restoreType($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
