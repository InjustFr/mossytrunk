<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

final class UnknownProductChosen extends InvalidOrder
{
    public function __construct()
    {
        parent::__construct('order.unknown_product_chosen');
    }
}
