<?php

declare(strict_types=1);

namespace App\Presentation\Api\SumUp;

use App\Application\SumUp\ImportFromSumUp\ImportFromSumUpHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class SumUpImportController extends AbstractController
{
    private const int TIME_LIMIT_SECONDS = 300;

    #[Route('/api/sumup/import', name: 'api_sumup_import', methods: ['POST'], format: 'json')]
    public function __invoke(ImportFromSumUpHandler $import): JsonResponse
    {
        set_time_limit(self::TIME_LIMIT_SECONDS);

        return $this->json($import());
    }
}
