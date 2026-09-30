<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class UnknownVariant extends InvalidProduct
{
    public function __construct(string $productName, string $variant)
    {
        parent::__construct(\sprintf('« %s » n\'est pas une variante de « %s ».', $variant, $productName));
    }
}
