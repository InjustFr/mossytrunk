<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class SupplyIsNotSold extends InvalidProduct
{
    public function __construct(string $product)
    {
        parent::__construct('product.supply_not_sold', ['product' => $product]);
    }
}
