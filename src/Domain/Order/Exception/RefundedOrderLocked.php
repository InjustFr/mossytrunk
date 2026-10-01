<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

final class RefundedOrderLocked extends InvalidOrder
{
    public function __construct(string $reference)
    {
        parent::__construct('order.refunded_locked', ['reference' => $reference]);
    }
}
