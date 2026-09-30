<?php

declare(strict_types=1);

namespace App\Domain\Discount\Exception;

final class DiscountStartedToday extends InvalidDiscountRule
{
    public function __construct()
    {
        parent::__construct('discount.started_today');
    }
}
