<?php

declare(strict_types=1);

namespace App\Application\Integration\Exception;

final class MissingSetting extends ServiceUnavailable
{
    public function __construct(string $name)
    {
        parent::__construct('integration.missing_setting', ['name' => $name]);
    }
}
