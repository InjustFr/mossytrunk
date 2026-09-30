<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\GetDesign\GetDesignHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/designs/{id}', name: 'api_designs_show', requirements: ['id' => Requirement::ULID], methods: ['GET'], format: 'json')]
final class GetDesignController extends AbstractController
{
    public function __invoke(string $id, GetDesignHandler $getDesign): JsonResponse
    {
        return $this->json($getDesign($id));
    }
}
