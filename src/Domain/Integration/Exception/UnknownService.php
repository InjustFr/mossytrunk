<?php

declare(strict_types=1);

namespace App\Domain\Integration\Exception;

final class UnknownService extends InvalidConnection
{
    public function __construct(string $service)
    {
        parent::__construct('integration.unknown_service', ['service' => $service]);
    }
}
