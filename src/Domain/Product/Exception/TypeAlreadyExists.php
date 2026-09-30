<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class TypeAlreadyExists extends InvalidProduct
{
    public function __construct(string $name)
    {
        parent::__construct('product.type_already_exists', ['name' => $name]);
    }
}
