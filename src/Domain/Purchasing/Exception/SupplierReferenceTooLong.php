<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Exception;

final class SupplierReferenceTooLong extends InvalidPurchase
{
    public function __construct(int $max)
    {
        parent::__construct('purchasing.supplier_reference_too_long', ['max' => $max]);
    }
}
