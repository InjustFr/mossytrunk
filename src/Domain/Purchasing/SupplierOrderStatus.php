<?php

declare(strict_types=1);

namespace App\Domain\Purchasing;

enum SupplierOrderStatus: string
{
    case Ordered = 'ordered';
    case Received = 'received';
}
