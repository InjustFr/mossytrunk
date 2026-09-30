<?php

declare(strict_types=1);

namespace App\Presentation\Api\Accounting;

use App\Application\Accounting\ChoosePeriodicity\ChoosePeriodicityHandler;
use App\Domain\Accounting\DeclarationPeriodicity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/accounting/urssaf/periodicity', name: 'api_accounting_periodicity', methods: ['PUT'], format: 'json')]
final class ChoosePeriodicityController extends AbstractController
{
    public function __invoke(Request $request, ChoosePeriodicityHandler $choose, TranslatorInterface $translator): Response
    {
        $periodicity = DeclarationPeriodicity::tryFrom($request->getPayload()->getString('periodicity'))
            ?? throw new UnprocessableEntityHttpException($translator->trans('problem.unknown_periodicity'));
        $choose($periodicity);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
