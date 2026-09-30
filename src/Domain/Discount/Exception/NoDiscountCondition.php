<?php

declare(strict_types=1);

namespace App\Domain\Discount\Exception;

final class NoDiscountCondition extends InvalidDiscountRule
{
    public function __construct()
    {
        parent::__construct('discount.no_condition');
    }
}
