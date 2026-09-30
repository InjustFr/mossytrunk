<?php

declare(strict_types=1);

namespace App\Domain\Discount\Exception;

final class NoDiscountCondition extends InvalidDiscountRule
{
    public function __construct()
    {
        parent::__construct('Ajoutez au moins une condition à la remise.');
    }
}
