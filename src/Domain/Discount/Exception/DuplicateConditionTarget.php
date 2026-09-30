<?php

declare(strict_types=1);

namespace App\Domain\Discount\Exception;

final class DuplicateConditionTarget extends InvalidDiscountRule
{
    public function __construct(string $targetName)
    {
        parent::__construct(\sprintf('« %s » apparaît dans plusieurs conditions : regroupez-les en une seule.', $targetName));
    }
}
