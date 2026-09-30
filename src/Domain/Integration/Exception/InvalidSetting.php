<?php

declare(strict_types=1);

namespace App\Domain\Integration\Exception;

final class InvalidSetting extends InvalidConnection
{
    public function __construct(string $name)
    {
        parent::__construct(\sprintf('Paramètre invalide « %s ».', $name));
    }
}
