<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\UpdateProduct\UpdateProduct;
use App\Application\Product\UpdateProduct\UpdateProductHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/products/{id}', name: 'api_products_update', requirements: ['id' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class UpdateProductController extends AbstractController
{
    public function __invoke(string $id, #[MapRequestPayload] ProductPayload $payload, UpdateProductHandler $updateProduct): Response
    {
        $updateProduct(new UpdateProduct(
            $id,
            $payload->name,
            $payload->sellingPrice,
            $payload->variants,
            $payload->typeId,
            $payload->lowStockThreshold,
            $payload->reference,
            $payload->pricesByChannel(),
        ));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
