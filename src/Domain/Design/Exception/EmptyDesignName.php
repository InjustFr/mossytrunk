<?php

declare(strict_types=1);

namespace App\Domain\Design\Exception;

final class EmptyDesignName extends InvalidDesign
{
    public function __construct(string $what)
    {
        parent::__construct(\sprintf('Le nom %s est obligatoire.', $what));
    }
}
