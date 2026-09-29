<?php

declare(strict_types=1);

namespace App\Domain\Discount;

enum DiscountActionKind: string
{
    case FixedPrice = 'fixedPrice';
    case AmountOff = 'amountOff';
    case PercentOff = 'percentOff';
}
