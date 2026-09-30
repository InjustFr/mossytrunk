<?php

declare(strict_types=1);

namespace App\Domain\Design\Exception;

final class SameProductTwice extends InvalidDesign
{
    public function __construct(string $name)
    {
        parent::__construct('design.same_product_twice', ['name' => $name]);
    }
}
