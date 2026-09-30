<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Exception;

final class EmptySupplierOrder extends InvalidPurchase
{
    public function __construct()
    {
        parent::__construct('purchasing.empty_supplier_order');
    }
}
