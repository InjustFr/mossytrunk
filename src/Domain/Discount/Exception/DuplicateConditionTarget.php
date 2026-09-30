<?php

declare(strict_types=1);

namespace App\Domain\Discount\Exception;

final class DuplicateConditionTarget extends InvalidDiscountRule
{
    public function __construct(string $targetName)
    {
        parent::__construct('discount.duplicate_condition_target', ['name' => $targetName]);
    }
}
