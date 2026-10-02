<?php

declare(strict_types=1);

namespace App\Presentation\Api\Reporting;

use App\Application\Reporting\ProductsReport\GetProductsReportHandler;
use App\Application\Reporting\ReportPeriod;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reports/products', name: 'api_reports_products', methods: ['GET'], format: 'json')]
final class GetProductsReportController extends AbstractController
{
    public function __invoke(GetProductsReportHandler $getReport, #[MapQueryParameter] string $period = ReportPeriod::LAST_TWELVE_MONTHS): JsonResponse
    {
        return $this->json($getReport($period));
    }
}
