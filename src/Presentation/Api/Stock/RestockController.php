<?php

declare(strict_types=1);

namespace App\Presentation\Api\Stock;

use App\Application\Stock\Restock\RestockHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/stock/restock', name: 'api_stock_restock', methods: ['POST'], format: 'json')]
final class RestockController extends AbstractController
{
    public function __invoke(#[MapRequestPayload] RestockPayload $payload, RestockHandler $restock): Response
    {
        $restock($payload->toCommand());

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
