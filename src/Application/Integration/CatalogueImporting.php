<?php

declare(strict_types=1);

namespace App\Application\Integration;

interface CatalogueImporting extends SalesConnector
{
    /**
     * @return list<ExternalLine>
     */
    public function catalogueLines(string $file): array;
}
