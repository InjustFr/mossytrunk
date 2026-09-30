<?php

declare(strict_types=1);

namespace App\Domain\Discount\Exception;

final class DiscountExpired extends InvalidDiscountRule
{
    public function __construct(string $name)
    {
        parent::__construct('discount.expired', ['name' => $name]);
    }
}
