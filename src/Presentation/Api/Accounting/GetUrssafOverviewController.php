<?php

declare(strict_types=1);

namespace App\Presentation\Api\Accounting;

use App\Application\Accounting\GetUrssafOverview\GetUrssafOverviewHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/accounting/urssaf', name: 'api_accounting_urssaf', methods: ['GET'], format: 'json')]
final class GetUrssafOverviewController extends AbstractController
{
    public function __invoke(GetUrssafOverviewHandler $overview, #[MapQueryParameter] ?int $year = null): JsonResponse
    {
        return $this->json($overview($year));
    }
}
