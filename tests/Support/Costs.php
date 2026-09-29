<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Product\Product;
use App\Domain\Shared\Money;

final class Costs
{
    public static function bought(Product $product, int $cents): Product
    {
        $product->bought(Money::cents($cents));

        return $product;
    }
}
