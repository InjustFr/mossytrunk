<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\UpdateProductType\UpdateProductTypeHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/product-types/{id}', name: 'api_product_types_update', requirements: ['id' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class UpdateProductTypeController extends AbstractController
{
    public function __invoke(string $id, #[MapRequestPayload] UpdateProductTypePayload $payload, UpdateProductTypeHandler $updateType): Response
    {
        $updateType($id, $payload->name, $payload->color, $payload->code);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
