<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class EmptyVariant extends InvalidProduct
{
    public function __construct()
    {
        parent::__construct('product.empty_variant');
    }
}
