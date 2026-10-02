<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

final class NegativePostage extends InvalidOrder
{
    public function __construct()
    {
        parent::__construct('order.negative_postage');
    }
}
