<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class UnknownTypeVariant extends InvalidProduct
{
    public function __construct(string $typeName, string $variant)
    {
        parent::__construct('product.unknown_type_variant', ['type' => $typeName, 'variant' => $variant]);
    }
}
