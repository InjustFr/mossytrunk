<?php

declare(strict_types=1);

namespace App\Application\Notebook\GetNotebookTemplate;

use App\Application\Notebook\NotebookTemplates;

final readonly class GetNotebookTemplateHandler
{
    public function __construct(private NotebookTemplates $templates)
    {
    }

    public function __invoke(): NotebookTemplateView
    {
        return NotebookTemplateView::of($this->templates->current());
    }
}
