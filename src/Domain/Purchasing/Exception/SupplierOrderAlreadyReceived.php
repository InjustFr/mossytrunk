<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Exception;

final class SupplierOrderAlreadyReceived extends InvalidPurchase
{
    public function __construct(string $reference)
    {
        parent::__construct(\sprintf('La commande %s est déjà réceptionnée : elle ne peut plus changer.', $reference));
    }
}
