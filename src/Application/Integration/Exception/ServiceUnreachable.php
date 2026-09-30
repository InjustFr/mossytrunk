<?php

declare(strict_types=1);

namespace App\Application\Integration\Exception;

final class ServiceUnreachable extends ServiceUnavailable
{
    public function __construct(string $label, string $reason)
    {
        parent::__construct('integration.service_unreachable', ['service' => $label, 'reason' => $reason]);
    }
}
