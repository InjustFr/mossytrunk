<?php

declare(strict_types=1);

namespace App\Domain\Sales\Exception;

final class ServiceAlreadyLinked extends InvalidSalesChannel
{
    public function __construct(string $service, string $channel)
    {
        parent::__construct('sales.service_already_linked', ['service' => $service, 'channel' => $channel]);
    }
}
