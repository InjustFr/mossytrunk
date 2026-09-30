<?php

declare(strict_types=1);

namespace App\Domain\Stock\Exception;

final class NegativeCount extends InvalidStock
{
    public function __construct()
    {
        parent::__construct('stock.negative_count');
    }
}
