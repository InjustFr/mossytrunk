<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Exception;

final class SupplierAlreadyExists extends InvalidPurchase
{
    public function __construct(string $name)
    {
        parent::__construct('purchasing.supplier_already_exists', ['name' => $name]);
    }
}
