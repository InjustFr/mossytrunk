<?php

declare(strict_types=1);

namespace App\Domain\Sales\Exception;

final class NotASupply extends InvalidSalesChannel
{
    public function __construct(string $product)
    {
        parent::__construct('sales.not_a_supply', ['product' => $product]);
    }
}
