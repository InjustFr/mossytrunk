<?php

declare(strict_types=1);

namespace App\Presentation\Api\Accounting;

use App\Application\Accounting\ChoosePeriodicity\ChoosePeriodicityHandler;
use App\Application\Accounting\DeclarePeriod\DeclarePeriodHandler;
use App\Application\Accounting\ExportOrders\ExportOrdersHandler;
use App\Application\Accounting\GetUrssafOverview\GetUrssafOverviewHandler;
use App\Domain\Accounting\DeclarationPeriodicity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/accounting', format: 'json')]
final class AccountingController extends AbstractController
{
    private const string PERIOD = '\d{4}-(\d{2}|T[1-4])';
    private const string DAY = '/^\d{4}-\d{2}-\d{2}$/';

    #[Route('/urssaf', name: 'api_accounting_urssaf', methods: ['GET'])]
    public function urssaf(GetUrssafOverviewHandler $overview, #[MapQueryParameter] ?int $year = null): JsonResponse
    {
        return $this->json($overview($year));
    }

    #[Route('/urssaf/periodicity', name: 'api_accounting_periodicity', methods: ['PUT'])]
    public function periodicity(Request $request, ChoosePeriodicityHandler $choose): Response
    {
        $periodicity = DeclarationPeriodicity::tryFrom($request->getPayload()->getString('periodicity'))
            ?? throw new UnprocessableEntityHttpException('Périodicité inconnue.');
        $choose($periodicity);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/urssaf/{period}/declaration', name: 'api_accounting_declare', requirements: ['period' => self::PERIOD], methods: ['PUT'])]
    public function declare(string $period, DeclarePeriodHandler $declarations): Response
    {
        $declarations->declare($period);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/urssaf/{period}/declaration', name: 'api_accounting_withdraw', requirements: ['period' => self::PERIOD], methods: ['DELETE'])]
    public function withdraw(string $period, DeclarePeriodHandler $declarations): Response
    {
        $declarations->withdraw($period);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/orders.csv', name: 'api_accounting_orders_csv', methods: ['GET'], format: 'csv')]
    public function exportOrders(ExportOrdersHandler $export, #[MapQueryParameter(filter: \FILTER_VALIDATE_REGEXP, options: ['regexp' => self::DAY])] string $from, #[MapQueryParameter(filter: \FILTER_VALIDATE_REGEXP, options: ['regexp' => self::DAY])] string $to): Response
    {
        $csv = $export($from, $to);

        return new Response($csv->content, headers: [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $csv->filename),
            'X-Order-Count' => (string) $csv->orderCount,
        ]);
    }
}
