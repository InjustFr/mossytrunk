<?php

declare(strict_types=1);

namespace App\Presentation\Api\Dashboard;

use App\Application\Dashboard\GetDashboard\GetDashboardHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/api/dashboard', name: 'api_dashboard', methods: ['GET'], format: 'json')]
    public function __invoke(GetDashboardHandler $getDashboard, #[MapQueryParameter(options: ['min_range' => 2000, 'max_range' => 2100])] ?int $year = null): JsonResponse
    {
        return $this->json($getDashboard($year));
    }
}
