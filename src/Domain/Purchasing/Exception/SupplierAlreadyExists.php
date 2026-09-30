<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Exception;

final class SupplierAlreadyExists extends InvalidPurchase
{
    public function __construct(string $name)
    {
        parent::__construct(\sprintf('Le fournisseur « %s » existe déjà.', $name));
    }
}
