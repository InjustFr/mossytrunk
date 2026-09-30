<?php

declare(strict_types=1);

namespace App\Domain\Stock\Exception;

final class NonPositiveStockQuantity extends InvalidStock
{
    public function __construct()
    {
        parent::__construct('stock.non_positive_quantity');
    }
}
