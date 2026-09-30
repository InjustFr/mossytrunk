<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\ValidateCollection\ValidateCollectionHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/design-collections/{id}/validation', name: 'api_design_collections_validate', requirements: ['id' => Requirement::ULID], methods: ['POST'], format: 'json')]
final class ValidateCollectionController extends AbstractController
{
    public function __invoke(string $id, ValidateCollectionHandler $validate): JsonResponse
    {
        return $this->json(['productsCreated' => $validate($id)]);
    }
}
