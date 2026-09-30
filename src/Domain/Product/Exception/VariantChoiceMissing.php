<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class VariantChoiceMissing extends InvalidProduct
{
    public function __construct(string $productName, string $typeName)
    {
        parent::__construct('product.variant_choice_missing', ['product' => $productName, 'type' => $typeName]);
    }
}
