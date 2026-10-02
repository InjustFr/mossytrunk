<?php

declare(strict_types=1);

namespace App\Domain\Purchasing;

enum Currency: string
{
    case Euro = 'EUR';
    case Dollar = 'USD';
}
