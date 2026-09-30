<?php

declare(strict_types=1);

namespace App\Domain\Discount\Exception;

final class ConditionWithoutTarget extends InvalidDiscountRule
{
    public function __construct()
    {
        parent::__construct('discount.condition_without_target');
    }
}
