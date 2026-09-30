<?php

declare(strict_types=1);

namespace App\Application\Integration\Exception;

final class ServiceNotAdded extends ServiceUnavailable
{
    public function __construct(string $label)
    {
        parent::__construct(\sprintf('%s n\'est pas ajouté : ajoutez-le dans Paramètres › Services connectés.', $label));
    }
}
