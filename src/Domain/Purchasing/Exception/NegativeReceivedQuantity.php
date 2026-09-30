<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Exception;

final class NegativeReceivedQuantity extends InvalidPurchase
{
    public function __construct(string $label)
    {
        parent::__construct('purchasing.negative_received_quantity', ['item' => $label]);
    }
}
