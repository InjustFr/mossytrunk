<?php

declare(strict_types=1);

namespace App\Application\Integration\Exception;

final class ServiceNotConfigured extends ServiceUnavailable
{
    public function __construct(string $label)
    {
        parent::__construct(\sprintf('%s n\'est pas configuré : complétez ses accès dans Paramètres › Services connectés.', $label));
    }
}
