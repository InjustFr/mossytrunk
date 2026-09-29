<?php

declare(strict_types=1);

namespace App\Domain\Stock;

enum LotOrigin: string
{
    case Purchase = 'purchase';
    case SupplierOrder = 'supplier_order';
    case Correction = 'correction';
    case Return = 'return';
}
