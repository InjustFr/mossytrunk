<?php

declare(strict_types=1);

namespace App\Domain\Notebook\Exception;

final class EmptyNotebookEntry extends InvalidNotebook
{
    public function __construct()
    {
        parent::__construct('notebook.empty_entry');
    }
}
