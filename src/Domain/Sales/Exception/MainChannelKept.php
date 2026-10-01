<?php

declare(strict_types=1);

namespace App\Domain\Sales\Exception;

final class MainChannelKept extends InvalidSalesChannel
{
    public function __construct(string $channel)
    {
        parent::__construct('sales.main_channel_kept', ['channel' => $channel]);
    }
}
