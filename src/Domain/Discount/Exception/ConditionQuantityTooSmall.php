<?php

declare(strict_types=1);

namespace App\Domain\Discount\Exception;

final class ConditionQuantityTooSmall extends InvalidDiscountRule
{
    public function __construct()
    {
        parent::__construct('discount.condition_quantity_too_small');
    }
}
