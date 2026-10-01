<?php

declare(strict_types=1);

namespace App\Application\Integration;

interface CatalogueExporting extends SalesConnector
{
    /**
     * @param list<CatalogueItem> $items
     */
    public function catalogue(array $items): string;
}
