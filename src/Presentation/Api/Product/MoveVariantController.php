<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\MoveVariant\MoveVariantHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/products/{id}/move-variant', name: 'api_products_move_variant', requirements: ['id' => Requirement::ULID], methods: ['POST'], format: 'json')]
final class MoveVariantController extends AbstractController
{
    public function __invoke(string $id, #[MapRequestPayload] MoveVariantPayload $payload, MoveVariantHandler $moveVariant): JsonResponse
    {
        return $this->json(['targetProductId' => (string) $moveVariant($payload->toCommand($id))]);
    }
}
