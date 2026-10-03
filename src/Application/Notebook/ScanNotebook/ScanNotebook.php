<?php

declare(strict_types=1);

namespace App\Application\Notebook\ScanNotebook;

use App\Application\Notebook\NotebookPage;

final readonly class ScanNotebook
{
    /**
     * @param list<NotebookPage> $pages
     */
    public function __construct(
        public string $eventId,
        public array $pages,
    ) {
    }
}
