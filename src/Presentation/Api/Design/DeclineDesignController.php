<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\DeclineDesign\DeclineDesignHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/designs/{id}/declinations', name: 'api_designs_decline', requirements: ['id' => Requirement::ULID], methods: ['POST'], format: 'json')]
final class DeclineDesignController extends AbstractController
{
    public function __invoke(string $id, Request $request, DeclineDesignHandler $decline): JsonResponse
    {
        return $this->json(['id' => (string) $decline($id, (string) $request->getPayload()->get('gabaritId'))], Response::HTTP_CREATED);
    }
}
