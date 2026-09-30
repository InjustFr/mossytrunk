<?php

declare(strict_types=1);

namespace App\Domain\Discount\Exception;

final class ValidityEndsBeforeStart extends InvalidDiscountRule
{
    public function __construct()
    {
        parent::__construct('discount.validity_ends_before_start');
    }
}
