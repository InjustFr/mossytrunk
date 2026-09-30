<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

final class NegativeLowStockThreshold extends InvalidProduct
{
    public function __construct()
    {
        parent::__construct('Le seuil de stock bas ne peut pas être négatif.');
    }
}
