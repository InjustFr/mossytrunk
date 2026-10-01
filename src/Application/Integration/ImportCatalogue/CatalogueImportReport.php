<?php

declare(strict_types=1);

namespace App\Application\Integration\ImportCatalogue;

final readonly class CatalogueImportReport
{
    public function __construct(
        public string $service,
        public string $label,
        public int $itemsRead,
        public int $itemsLinked,
        public int $productsCreated,
        public int $itemsToLink,
    ) {
    }
}
