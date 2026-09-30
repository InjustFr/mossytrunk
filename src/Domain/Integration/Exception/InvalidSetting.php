<?php

declare(strict_types=1);

namespace App\Domain\Integration\Exception;

final class InvalidSetting extends InvalidConnection
{
    public function __construct(string $name)
    {
        parent::__construct('integration.invalid_setting', ['name' => $name]);
    }
}
