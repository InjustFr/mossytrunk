<?php

declare(strict_types=1);

namespace App\Presentation\Api\Accounting;

use App\Application\Accounting\ExportOrders\ExportOrdersHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/accounting/orders.csv', name: 'api_accounting_orders_csv', methods: ['GET'], format: 'csv')]
final class ExportOrdersController extends AbstractController
{
    private const string DAY = '/^\d{4}-\d{2}-\d{2}$/';

    public function __invoke(ExportOrdersHandler $export, #[MapQueryParameter(filter: \FILTER_VALIDATE_REGEXP, options: ['regexp' => self::DAY])] string $from, #[MapQueryParameter(filter: \FILTER_VALIDATE_REGEXP, options: ['regexp' => self::DAY])] string $to): Response
    {
        $csv = $export($from, $to);

        return new Response($csv->content, headers: [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $csv->filename),
            'X-Order-Count' => (string) $csv->orderCount,
        ]);
    }
}
