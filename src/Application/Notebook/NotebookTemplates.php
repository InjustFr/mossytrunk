<?php

declare(strict_types=1);

namespace App\Application\Notebook;

use App\Application\WorkspaceContext;
use App\Domain\Notebook\NotebookTemplate;
use App\Domain\Notebook\NotebookTemplateRepository;

final readonly class NotebookTemplates
{
    public function __construct(
        private NotebookTemplateRepository $templates,
        private WorkspaceContext $workspace,
    ) {
    }

    public function current(): NotebookTemplate
    {
        $template = $this->templates->current();
        if (null === $template) {
            $template = NotebookTemplate::standard($this->workspace->current());
            $this->templates->add($template);
        }

        return $template;
    }
}
