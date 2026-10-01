<?php

declare(strict_types=1);

namespace App\Domain\Sales\Exception;

final class UnknownSalesService extends InvalidSalesChannel
{
    public function __construct(string $service)
    {
        parent::__construct('sales.unknown_service', ['service' => $service]);
    }
}
