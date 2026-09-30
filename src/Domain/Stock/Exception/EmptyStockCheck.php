<?php

declare(strict_types=1);

namespace App\Domain\Stock\Exception;

final class EmptyStockCheck extends InvalidStock
{
    public function __construct()
    {
        parent::__construct('stock.empty_stock_check');
    }
}
