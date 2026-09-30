<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Design\AttachProductToDesign\AttachProductToDesignHandler;
use App\Application\Design\DesignProduct\DesignProductHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/products/{id}/design', name: 'api_products_design', requirements: ['id' => Requirement::ULID], methods: ['POST'], format: 'json')]
final class DesignProductController extends AbstractController
{
    public function __invoke(string $id, Request $request, DesignProductHandler $designProduct, AttachProductToDesignHandler $attach): JsonResponse
    {
        $payload = $request->getPayload();
        $gabaritId = $payload->getString('gabaritId');
        $designId = $payload->getString('designId');
        $collectionId = $payload->getString('collectionId');

        $design = '' === $designId
            ? $designProduct($id, $gabaritId, '' === $collectionId ? null : $collectionId)
            : $attach($id, $designId, $gabaritId);

        return $this->json(['designId' => (string) $design], Response::HTTP_CREATED);
    }
}
