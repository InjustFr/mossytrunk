<?php

declare(strict_types=1);

namespace App\Application\Product\BatchUpdateProducts;

enum ChannelPriceMode: string
{
    case Fixed = 'fixed';
    case Derived = 'derived';
    case SellingPrice = 'selling_price';
}
