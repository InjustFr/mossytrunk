<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

final class SupplyNotOnChannel extends InvalidOrder
{
    public function __construct(string $supply, string $reference)
    {
        parent::__construct('order.supply_not_on_channel', ['supply' => $supply, 'reference' => $reference]);
    }
}
