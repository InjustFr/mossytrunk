<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

final class NegativeShippingCost extends InvalidOrder
{
    public function __construct()
    {
        parent::__construct('Les frais de port ne peuvent pas être négatifs.');
    }
}
