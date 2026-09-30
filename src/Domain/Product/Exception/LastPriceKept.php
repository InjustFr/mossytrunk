<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class LastPriceKept extends InvalidProduct
{
    public function __construct()
    {
        parent::__construct('Le produit doit garder au moins un prix.');
    }
}
