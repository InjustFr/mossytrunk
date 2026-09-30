<?php

declare(strict_types=1);

namespace App\Presentation\Api\Accounting;

use App\Application\Accounting\WithdrawDeclaration\WithdrawDeclarationHandler;
use App\Presentation\RouteRequirement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/accounting/urssaf/{period}/declaration', name: 'api_accounting_withdraw', requirements: ['period' => RouteRequirement::DECLARATION_PERIOD], methods: ['DELETE'], format: 'json')]
final class WithdrawDeclarationController extends AbstractController
{
    public function __invoke(string $period, WithdrawDeclarationHandler $withdraw): Response
    {
        $withdraw($period);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
