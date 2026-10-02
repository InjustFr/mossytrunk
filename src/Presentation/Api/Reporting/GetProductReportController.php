<?php

declare(strict_types=1);

namespace App\Presentation\Api\Reporting;

use App\Application\Reporting\ProductReport\GetProductReportHandler;
use App\Application\Reporting\ReportPeriod;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/reports/products/{id}', name: 'api_reports_product', requirements: ['id' => Requirement::ULID], methods: ['GET'], format: 'json')]
final class GetProductReportController extends AbstractController
{
    public function __invoke(string $id, GetProductReportHandler $getReport, #[MapQueryParameter] string $period = ReportPeriod::LAST_TWELVE_MONTHS): JsonResponse
    {
        return $this->json($getReport($id, $period));
    }
}
