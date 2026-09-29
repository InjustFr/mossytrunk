<?php

declare(strict_types=1);

namespace App\Domain\Order;

enum PaymentMethod: string
{
    case Card = 'card';
    case Cash = 'cash';
}
