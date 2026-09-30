<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\ListDesigns\ListDesignsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/designs', name: 'api_designs_board', methods: ['GET'], format: 'json')]
final class ListDesignsController extends AbstractController
{
    public function __invoke(ListDesignsHandler $listDesigns): JsonResponse
    {
        return $this->json($listDesigns());
    }
}
