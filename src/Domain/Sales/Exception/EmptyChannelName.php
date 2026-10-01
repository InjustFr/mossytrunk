<?php

declare(strict_types=1);

namespace App\Domain\Sales\Exception;

final class EmptyChannelName extends InvalidSalesChannel
{
    public function __construct()
    {
        parent::__construct('sales.empty_channel_name');
    }
}
