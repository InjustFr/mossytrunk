<?php

declare(strict_types=1);

namespace App\Domain\Design\Exception;

final class GabaritAlreadyExists extends InvalidDesign
{
    public function __construct(string $name)
    {
        parent::__construct(\sprintf('Le gabarit « %s » existe déjà.', $name));
    }
}
