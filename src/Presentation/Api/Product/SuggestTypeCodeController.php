<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\SuggestTypeCode\SuggestTypeCodeHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/product-types/code-suggestion', name: 'api_product_types_code_suggestion', methods: ['GET'], format: 'json')]
final class SuggestTypeCodeController extends AbstractController
{
    public function __invoke(SuggestTypeCodeHandler $suggest, #[MapQueryParameter] string $name = ''): JsonResponse
    {
        return $this->json(['code' => $suggest($name)]);
    }
}
