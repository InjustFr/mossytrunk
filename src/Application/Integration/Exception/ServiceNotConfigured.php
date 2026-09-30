<?php

declare(strict_types=1);

namespace App\Application\Integration\Exception;

final class ServiceNotConfigured extends ServiceUnavailable
{
    public function __construct(string $label)
    {
        parent::__construct('integration.service_not_configured', ['service' => $label]);
    }
}
