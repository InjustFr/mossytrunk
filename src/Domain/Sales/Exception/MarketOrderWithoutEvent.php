<?php

declare(strict_types=1);

namespace App\Domain\Sales\Exception;

final class MarketOrderWithoutEvent extends InvalidSalesChannel
{
    public function __construct(string $channel)
    {
        parent::__construct('sales.market_order_without_event', ['channel' => $channel]);
    }
}
