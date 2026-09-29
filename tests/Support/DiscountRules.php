<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Application\Discount\ConditionDefinition;
use App\Application\Discount\DiscountRuleDefinition;

final class DiscountRules
{
    public static function fixedPrice(string $name, int $cents, ConditionDefinition ...$conditions): DiscountRuleDefinition
    {
        return new DiscountRuleDefinition($name, array_values($conditions), 'fixedPrice', $cents);
    }

    public static function product(string $productId, int $quantity): ConditionDefinition
    {
        return new ConditionDefinition(ConditionDefinition::PRODUCT, $productId, $quantity);
    }

    public static function type(string $typeId, int $quantity): ConditionDefinition
    {
        return new ConditionDefinition(ConditionDefinition::TYPE, $typeId, $quantity);
    }
}
