<?php

declare(strict_types=1);

namespace App\Domain\Discount\Exception;

final class OnlyEligibleType extends InvalidDiscountRule
{
    public function __construct(string $ruleName, string $typeName)
    {
        parent::__construct('discount.only_eligible_type', ['rule' => $ruleName, 'type' => $typeName]);
    }
}
