<?php

declare(strict_types=1);

namespace App\Domain\Discount\Exception;

final class ValidityEndsBeforeStart extends InvalidDiscountRule
{
    public function __construct()
    {
        parent::__construct('La fin de validité ne peut pas précéder son début.');
    }
}
