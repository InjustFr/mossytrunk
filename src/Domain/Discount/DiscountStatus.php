<?php

declare(strict_types=1);

namespace App\Domain\Discount;

enum DiscountStatus: string
{
    case Running = 'running';
    case Upcoming = 'upcoming';
    case Expired = 'expired';
}
