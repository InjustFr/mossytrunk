<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\RecordSellingPrice\RecordSellingPriceHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/products/{id}/prices', name: 'api_products_prices_record', requirements: ['id' => Requirement::ULID], methods: ['POST'], format: 'json')]
final class RecordSellingPriceController extends AbstractController
{
    public function __invoke(string $id, #[MapRequestPayload] SellingPricePayload $payload, RecordSellingPriceHandler $prices): Response
    {
        $prices($id, $payload->price, $payload->since);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
