<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\AmendSellingPrice\AmendSellingPriceHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/products/{id}/prices/{changeId}', name: 'api_products_prices_amend', requirements: ['id' => Requirement::ULID, 'changeId' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class AmendSellingPriceController extends AbstractController
{
    public function __invoke(string $id, string $changeId, #[MapRequestPayload] SellingPricePayload $payload, AmendSellingPriceHandler $prices): Response
    {
        $prices($id, $changeId, $payload->price, $payload->since);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
