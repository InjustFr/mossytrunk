<?php

declare(strict_types=1);

namespace App\Application\Notebook;

use App\Domain\Notebook\NotebookEntry;

interface NotebookReader
{
    /**
     * @param list<NotebookPage> $pages
     *
     * @return list<NotebookEntry>
     */
    public function read(array $pages, NotebookCatalogue $catalogue): array;
}
