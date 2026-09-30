<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\SuggestProductReference\SuggestProductReferenceHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Ulid;

#[Route('/api/products/reference-suggestion', name: 'api_products_reference_suggestion', methods: ['GET'], format: 'json')]
final class SuggestProductReferenceController extends AbstractController
{
    public function __invoke(SuggestProductReferenceHandler $suggest, #[MapQueryParameter] string $name = '', #[MapQueryParameter] string $typeId = ''): JsonResponse
    {
        return $this->json(['reference' => $suggest(Ulid::isValid($typeId) ? Ulid::fromString($typeId) : null, $name)]);
    }
}
