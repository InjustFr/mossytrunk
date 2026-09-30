<?php

declare(strict_types=1);

namespace App\Presentation\Api\Design;

use App\Application\Design\ListGabarits\ListGabaritsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/gabarits', name: 'api_gabarits_list', methods: ['GET'], format: 'json')]
final class ListGabaritsController extends AbstractController
{
    public function __invoke(ListGabaritsHandler $listGabarits): JsonResponse
    {
        return $this->json($listGabarits());
    }
}
