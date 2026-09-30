<?php

declare(strict_types=1);

namespace App\Domain\Discount\Exception;

final class InvalidPercentage extends InvalidDiscountRule
{
    public function __construct()
    {
        parent::__construct('Le pourcentage de remise doit être compris entre 0 et 100 %.');
    }
}
