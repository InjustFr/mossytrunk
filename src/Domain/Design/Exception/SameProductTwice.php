<?php

declare(strict_types=1);

namespace App\Domain\Design\Exception;

final class SameProductTwice extends InvalidDesign
{
    public function __construct(string $name)
    {
        parent::__construct(\sprintf('Deux déclinaisons donneraient le même produit « %s » : renommez-en une.', $name));
    }
}
