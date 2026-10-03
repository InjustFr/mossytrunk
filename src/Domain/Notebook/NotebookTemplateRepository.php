<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

interface NotebookTemplateRepository
{
    public function add(NotebookTemplate $template): void;

    public function current(): ?NotebookTemplate;
}
