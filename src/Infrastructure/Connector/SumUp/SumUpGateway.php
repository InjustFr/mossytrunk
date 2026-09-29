<?php

declare(strict_types=1);

namespace App\Infrastructure\Connector\SumUp;

use App\Application\Integration\ExternalSale;

interface SumUpGateway
{
    /**
     * @return iterable<ExternalSale>
     */
    public function successfulPayments(SumUpCredentials $credentials): iterable;
}
