<?php

declare(strict_types=1);

namespace App\Application\Integration\Exception;

final class ServiceNotConnected extends ServiceUnavailable
{
    public function __construct(string $label)
    {
        parent::__construct(\sprintf('%s n\'est pas connecté : connectez-le depuis Paramètres › Services connectés.', $label));
    }
}
