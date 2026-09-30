<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class SoldWithoutVariant extends InvalidProduct
{
    public function __construct(string $productName)
    {
        parent::__construct('product.sold_without_variant', ['name' => $productName]);
    }
}
