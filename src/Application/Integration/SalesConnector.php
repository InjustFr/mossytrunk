<?php

declare(strict_types=1);

namespace App\Application\Integration;

interface SalesConnector
{
    public function describe(): ServiceDescription;

    /**
     * @return iterable<ExternalSale>
     */
    public function sales(Credentials $credentials): iterable;
}
