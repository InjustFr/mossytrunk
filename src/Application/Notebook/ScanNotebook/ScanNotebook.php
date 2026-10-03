<?php

declare(strict_types=1);

namespace App\Application\Notebook\ScanNotebook;

final readonly class ScanNotebook
{
    /**
     * @param list<string> $pages
     */
    public function __construct(
        public string $eventId,
        public array $pages,
    ) {
    }
}
