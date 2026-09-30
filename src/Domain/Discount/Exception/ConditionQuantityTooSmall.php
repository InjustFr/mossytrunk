<?php

declare(strict_types=1);

namespace App\Domain\Discount\Exception;

final class ConditionQuantityTooSmall extends InvalidDiscountRule
{
    public function __construct()
    {
        parent::__construct('Une condition porte sur au moins 1 article.');
    }
}
