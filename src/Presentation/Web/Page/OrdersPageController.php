<?php

declare(strict_types=1);

namespace App\Presentation\Web\Page;

use App\Presentation\Web\VuePage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/orders', name: 'orders', methods: ['GET'])]
final readonly class OrdersPageController
{
    public function __construct(private VuePage $page)
    {
    }

    public function __invoke(#[MapQueryParameter(filter: \FILTER_VALIDATE_REGEXP, flags: \FILTER_NULL_ON_FAILURE, options: ['regexp' => '/^[0-7][0-9A-HJKMNP-TV-Z]{25}$/'])] ?string $event = null): Response
    {
        $orders = null === $event ? '/api/orders' : "/api/orders?eventId=$event";

        return $this->page->render('OrdersPage', 'orders', preload: [$orders, '/api/products', '/api/events', '/api/services', '/api/sales-channels']);
    }
}
