<?php

declare(strict_types=1);

namespace App\Domain\Design\Exception;

final class UnknownAdaptation extends InvalidDesign
{
    public function __construct(string $adaptation)
    {
        parent::__construct('design.unknown_adjustment', ['adjustment' => $adaptation]);
    }
}
