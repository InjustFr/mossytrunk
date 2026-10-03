<?php

declare(strict_types=1);

namespace App\Presentation\Web\Page;

use App\Presentation\Web\VuePage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/settings', name: 'settings', methods: ['GET'])]
final readonly class SettingsPageController
{
    public function __construct(private VuePage $page)
    {
    }

    public function __invoke(): Response
    {
        return $this->page->render('SettingsPage', 'settings', preload: ['/api/workspace/settings', '/api/services', '/api/references/formats', '/api/notebook-template']);
    }
}
