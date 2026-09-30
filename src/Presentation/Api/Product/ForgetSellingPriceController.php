<?php

declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Application\Product\ForgetSellingPrice\ForgetSellingPriceHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/products/{id}/prices/{changeId}', name: 'api_products_prices_forget', requirements: ['id' => Requirement::ULID, 'changeId' => Requirement::ULID], methods: ['DELETE'], format: 'json')]
final class ForgetSellingPriceController extends AbstractController
{
    public function __invoke(string $id, string $changeId, ForgetSellingPriceHandler $prices): Response
    {
        $prices($id, $changeId);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
