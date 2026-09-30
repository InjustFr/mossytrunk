<?php

declare(strict_types=1);

namespace App\Domain\Stock\Exception;

final class NonPositiveStockQuantity extends InvalidStock
{
    public function __construct()
    {
        parent::__construct('La quantité doit être supérieure à zéro.');
    }
}
