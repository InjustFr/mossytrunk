<?php

declare(strict_types=1);

namespace App\Domain\Sales\Exception;

final class ChannelHasOrdersWithoutEvent extends InvalidSalesChannel
{
    public function __construct(string $channel, int $count)
    {
        parent::__construct('sales.channel_has_orders_without_event', ['channel' => $channel, 'count' => $count]);
    }
}
