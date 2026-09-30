<?php

declare(strict_types=1);

namespace App\Domain\Design\Exception;

final class DesignHasProducts extends InvalidDesign
{
    public function __construct(string $design)
    {
        parent::__construct('design.has_products', ['name' => $design]);
    }
}
