<?php

declare(strict_types=1);

namespace App\Domain\Design\Exception;

final class GabaritInUse extends InvalidDesign
{
    public function __construct(string $name, int $designs)
    {
        parent::__construct('design.gabarit_in_use', ['name' => $name, 'designs' => $designs]);
    }
}
