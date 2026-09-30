<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Exception;

final class ReceivedQuantityMissing extends InvalidPurchase
{
    public function __construct(string $label)
    {
        parent::__construct('purchasing.received_quantity_missing', ['item' => $label]);
    }
}
