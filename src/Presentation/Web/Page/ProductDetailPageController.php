<?php

declare(strict_types=1);

namespace App\Presentation\Web\Page;

use App\Presentation\Web\VuePage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[AsController]
#[Route('/products/{id}', name: 'product_show', requirements: ['id' => Requirement::ULID], methods: ['GET'])]
final readonly class ProductDetailPageController
{
    public function __construct(private VuePage $page)
    {
    }

    public function __invoke(string $id): Response
    {
        return $this->page->render('ProductDetailPage', 'product', ['productId' => $id], preload: ["/api/products/$id", '/api/gabarits', '/api/designs', '/api/product-types', "/api/products/$id/stock"]);
    }
}
