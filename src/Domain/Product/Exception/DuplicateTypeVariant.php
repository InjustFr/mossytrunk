<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class DuplicateTypeVariant extends InvalidProduct
{
    public function __construct(string $variant)
    {
        parent::__construct('product.duplicate_type_variant', ['variant' => $variant]);
    }
}
