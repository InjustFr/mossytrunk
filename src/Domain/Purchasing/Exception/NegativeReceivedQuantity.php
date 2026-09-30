<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Exception;

final class NegativeReceivedQuantity extends InvalidPurchase
{
    public function __construct(string $label)
    {
        parent::__construct(\sprintf('La quantité reçue de « %s » ne peut pas être négative.', $label));
    }
}
