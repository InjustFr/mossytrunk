<?php

declare(strict_types=1);

namespace App\Domain\Integration\Exception;

final class ServiceAlreadyAdded extends InvalidConnection
{
    public function __construct(string $label)
    {
        parent::__construct('integration.service_already_added', ['service' => $label]);
    }
}
