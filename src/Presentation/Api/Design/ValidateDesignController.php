<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\ValidateDesign\ValidateDesignHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/designs/{id}/validation', name: 'api_designs_validate', requirements: ['id' => Requirement::ULID], methods: ['POST'], format: 'json')]
final class ValidateDesignController extends AbstractController
{
    public function __invoke(string $id, ValidateDesignHandler $validate): JsonResponse
    {
        return $this->json(['productsCreated' => $validate($id)]);
    }
}
