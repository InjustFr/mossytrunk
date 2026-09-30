<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class VariantMovedOntoItself extends InvalidProduct
{
    public function __construct()
    {
        parent::__construct('product.variant_moved_onto_itself');
    }
}
