<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class VariantInUse extends InvalidProduct
{
    public function __construct(string $variant)
    {
        parent::__construct('product.variant_in_use', ['variant' => $variant]);
    }
}
