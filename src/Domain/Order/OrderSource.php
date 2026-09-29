<?php

declare(strict_types=1);

namespace App\Domain\Order;

enum OrderSource: string
{
    case Manual = 'manual';
    case SumUp = 'sumup';
    case Etsy = 'etsy';
}
