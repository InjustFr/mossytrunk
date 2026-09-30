<?php

declare(strict_types=1);

namespace App\Presentation\Web\Page;

use App\Presentation\Web\VuePage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[AsController]
#[Route('/evenements/{id}/inventaire', name: 'event_stock_check', requirements: ['id' => Requirement::ULID], methods: ['GET'])]
final readonly class StockCheckPageController
{
    public function __construct(private VuePage $page)
    {
    }

    public function __invoke(string $id): Response
    {
        return $this->page->render('StockCheckPage', 'Inventaire', ['eventId' => $id]);
    }
}
