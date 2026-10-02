<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Exception;

final class SupplierOrdersNotMergeable extends InvalidPurchase
{
    public function __construct(string $reason)
    {
        parent::__construct('purchasing.supplier_orders_not_mergeable', ['reason' => $reason]);
    }
}
