<?php

declare(strict_types=1);

namespace App\Presentation\Api\SumUp;

use App\Application\SumUp\ImportFromSumUp\ImportFromSumUpHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class SumUpImportController extends AbstractController
{
    #[Route('/api/sumup/import', name: 'api_sumup_import', methods: ['POST'], format: 'json')]
    public function __invoke(ImportFromSumUpHandler $import): JsonResponse
    {
        return $this->json($import());
    }
}
