<?php

declare(strict_types=1);

namespace App\Application\Integration\ExportCatalogue;

final readonly class CatalogueFile
{
    public function __construct(
        public string $filename,
        public string $content,
        public int $itemCount,
    ) {
    }
}
