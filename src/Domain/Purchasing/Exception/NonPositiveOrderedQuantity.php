<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Exception;

final class NonPositiveOrderedQuantity extends InvalidPurchase
{
    public function __construct()
    {
        parent::__construct('purchasing.non_positive_ordered_quantity');
    }
}
