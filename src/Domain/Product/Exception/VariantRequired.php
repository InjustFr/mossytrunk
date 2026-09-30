<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class VariantRequired extends InvalidProduct
{
    public function __construct(string $productName)
    {
        parent::__construct(\sprintf('Choisissez une variante pour « %s ».', $productName));
    }
}
