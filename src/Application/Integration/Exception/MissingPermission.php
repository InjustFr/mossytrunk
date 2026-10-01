<?php

declare(strict_types=1);

namespace App\Application\Integration\Exception;

final class MissingPermission extends ServiceUnavailable
{
    public function __construct(string $label)
    {
        parent::__construct('integration.missing_permission', ['service' => $label]);
    }
}
