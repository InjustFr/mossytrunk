<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Exception;

final class ReceivedQuantityMissing extends InvalidPurchase
{
    public function __construct(string $label)
    {
        parent::__construct(\sprintf('Indiquez la quantité reçue pour « %s ».', $label));
    }
}
