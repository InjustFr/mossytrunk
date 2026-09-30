<?php

declare(strict_types=1);

namespace App\Application\Integration\Exception;

final class ServiceNotAdded extends ServiceUnavailable
{
    public function __construct(string $label)
    {
        parent::__construct('integration.service_not_added', ['service' => $label]);
    }
}
