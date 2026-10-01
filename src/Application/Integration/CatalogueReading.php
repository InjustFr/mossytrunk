<?php

declare(strict_types=1);

namespace App\Application\Integration;

interface CatalogueReading extends SalesConnector
{
    /**
     * @return iterable<ExternalLine>
     */
    public function catalogueLines(Credentials $credentials): iterable;
}
