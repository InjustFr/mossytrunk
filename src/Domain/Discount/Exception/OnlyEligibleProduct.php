<?php

declare(strict_types=1);

namespace App\Domain\Discount\Exception;

final class OnlyEligibleProduct extends InvalidDiscountRule
{
    public function __construct(string $ruleName, string $productName)
    {
        parent::__construct(\sprintf('La remise « %s » ne concerne que « %s » : modifiez ou supprimez-la avant de supprimer le produit.', $ruleName, $productName));
    }
}
