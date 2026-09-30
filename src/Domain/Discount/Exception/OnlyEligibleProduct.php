<?php

declare(strict_types=1);

namespace App\Domain\Discount\Exception;

final class OnlyEligibleProduct extends InvalidDiscountRule
{
    public function __construct(string $ruleName, string $productName)
    {
        parent::__construct('discount.only_eligible_product', ['rule' => $ruleName, 'product' => $productName]);
    }
}
