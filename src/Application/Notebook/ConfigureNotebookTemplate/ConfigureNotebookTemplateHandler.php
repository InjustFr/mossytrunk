<?php

declare(strict_types=1);

namespace App\Application\Notebook\ConfigureNotebookTemplate;

use App\Application\Notebook\NotebookTemplates;
use App\Application\Transaction;

final readonly class ConfigureNotebookTemplateHandler
{
    public function __construct(
        private NotebookTemplates $templates,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(ConfigureNotebookTemplate $command): void
    {
        $this->templates->current()->configure($command->separation, $command->abbreviations);
        $this->transaction->commit();
    }
}
