<?php

declare(strict_types=1);

namespace App\Presentation\Api\Accounting;

use App\Application\Accounting\DeclarePeriod\DeclarePeriodHandler;
use App\Presentation\RouteRequirement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/accounting/urssaf/{period}/declaration', name: 'api_accounting_declare', requirements: ['period' => RouteRequirement::DECLARATION_PERIOD], methods: ['PUT'], format: 'json')]
final class DeclarePeriodController extends AbstractController
{
    public function __invoke(string $period, DeclarePeriodHandler $declare): Response
    {
        $declare($period);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
