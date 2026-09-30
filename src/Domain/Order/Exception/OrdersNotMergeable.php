<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

final class OrdersNotMergeable extends InvalidOrder
{
    public function __construct(string $reason)
    {
        parent::__construct('order.not_mergeable', ['reason' => $reason]);
    }
}
