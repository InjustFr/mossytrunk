<?php

declare(strict_types=1);

namespace App\Domain\Sales;

enum ChannelCostKind: string
{
    case Fixed = 'fixed';
    case Percent = 'percent';
}
