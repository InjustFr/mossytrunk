<?php

declare(strict_types=1);

namespace App\Application\Integration\Exception;

final class ServiceNotRegistered extends ServiceUnavailable
{
    public function __construct(string $service)
    {
        parent::__construct(\sprintf('Service inconnu « %s ».', $service));
    }
}
