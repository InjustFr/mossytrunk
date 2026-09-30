<?php

declare(strict_types=1);

namespace App\Domain\Design\Exception;

final class NothingToValidate extends InvalidDesign
{
    public function __construct(string $design)
    {
        parent::__construct('design.nothing_to_validate', ['name' => $design]);
    }
}
