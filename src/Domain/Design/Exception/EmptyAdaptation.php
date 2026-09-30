<?php

declare(strict_types=1);

namespace App\Domain\Design\Exception;

final class EmptyAdaptation extends InvalidDesign
{
    public function __construct()
    {
        parent::__construct('design.empty_adjustment');
    }
}
