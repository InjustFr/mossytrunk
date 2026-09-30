<?php

declare(strict_types=1);

namespace App\Presentation\Web\Page;

use App\Presentation\Web\VuePage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[AsController]
#[Route('/evenements/{id}', name: 'event_show', requirements: ['id' => Requirement::ULID], methods: ['GET'])]
final readonly class EventDetailPageController
{
    public function __construct(private VuePage $page)
    {
    }

    public function __invoke(string $id): Response
    {
        return $this->page->render('EventDetailPage', 'Événement', ['eventId' => $id]);
    }
}
