<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class SoldWithoutVariant extends InvalidProduct
{
    public function __construct(string $productName)
    {
        parent::__construct(\sprintf('« %s » a déjà des ventes sans variante : déplacez-le d\'abord lui-même vers une variante.', $productName));
    }
}
