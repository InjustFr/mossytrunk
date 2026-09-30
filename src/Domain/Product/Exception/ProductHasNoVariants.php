<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class ProductHasNoVariants extends InvalidProduct
{
    public function __construct(string $productName)
    {
        parent::__construct(\sprintf('« %s » est un produit unique : il n\'a pas de variante.', $productName));
    }
}
