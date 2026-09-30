<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Exception;

final class EmptySupplierOrder extends InvalidPurchase
{
    public function __construct()
    {
        parent::__construct('Ajoutez au moins un produit à la commande.');
    }
}
