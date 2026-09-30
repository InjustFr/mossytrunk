<?php

declare(strict_types=1);

namespace App\Domain\Discount\Exception;

final class InvalidPercentage extends InvalidDiscountRule
{
    public function __construct()
    {
        parent::__construct('discount.invalid_percentage');
    }
}
