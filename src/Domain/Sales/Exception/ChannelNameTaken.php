<?php

declare(strict_types=1);

namespace App\Domain\Sales\Exception;

final class ChannelNameTaken extends InvalidSalesChannel
{
    public function __construct(string $name)
    {
        parent::__construct('sales.channel_name_taken', ['name' => $name]);
    }
}
