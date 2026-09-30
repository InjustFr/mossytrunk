<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Exception;

final class SupplierOrderAlreadyReceived extends InvalidPurchase
{
    public function __construct(string $reference)
    {
        parent::__construct('purchasing.supplier_order_already_received', ['reference' => $reference]);
    }
}
