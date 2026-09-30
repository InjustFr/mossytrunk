<?php

declare(strict_types=1);

namespace App\Domain\Design\Exception;

final class DuplicateAdaptation extends InvalidDesign
{
    public function __construct(string $adaptation)
    {
        parent::__construct('design.duplicate_adjustment', ['adjustment' => $adaptation]);
    }
}
