<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class TypeStillUsed extends InvalidProduct
{
    public function __construct(string $typeName, int $products, int $gabarits)
    {
        parent::__construct('product.type_still_used', ['type' => $typeName, 'products' => $products, 'gabarits' => $gabarits]);
    }
}
