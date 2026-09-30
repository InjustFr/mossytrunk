<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Exception;

final class UnknownProductPurchased extends InvalidPurchase
{
    public function __construct(string $label)
    {
        parent::__construct('purchasing.unknown_product_purchased', ['label' => $label]);
    }
}
